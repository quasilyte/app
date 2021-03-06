<?php

namespace Wikia\Rabbit;

use PhpAmqpLib\Exception\AMQPExceptionInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Wikia\CircuitBreaker\ServiceCircuitBreaker;
use Wikia\Logger\Loggable;
use Wikia\Tasks\AsyncTaskList;
use Wikia\Tracer\WikiaTracer;

/**
 * A default task publisher implementation that publishes tasks to RabbitMQ.
 */
class DefaultTaskPublisher implements TaskPublisher {

	use Loggable;

	const TASK_PUBLISHER_CIRCUIT_BREAKER_NAME = 'task_publisher';

	/** @var ConnectionManager $rabbitConnectionManager */
	private $rabbitConnectionManager;

	/** @var TaskProducer[] $producers task producers registered for publish */
	private $producers = [];

	/** @var AsyncTaskList[] $tasks LIFO queue storing tasks to be published */
	private $tasks = [];

	/** @var ServiceCircuitBreaker */
	private $circuitBreaker;

	public function __construct( ConnectionManager $rabbitConnectionManager, ServiceCircuitBreaker $circuitBreaker ) {
		$this->rabbitConnectionManager = $rabbitConnectionManager;
		$this->circuitBreaker = $circuitBreaker;

		// Schedule doUpdate() to be executed at the end of the request
		\Hooks::register( 'RestInPeace', [ $this, 'doUpdate' ] );
	}

	/**
	 * Push a task to be queued.
	 * @param AsyncTaskList $task
	 * @return string ID of the task
	 */
	public function pushTask( AsyncTaskList $task ): string {
		$this->tasks[] = $task;

		return $task->getId();
	}

	public function registerProducer( TaskProducer $producer ) {
		$this->producers[] = $producer;
	}

	/**
	 * Publish queued tasks to RabbitMQ.
	 * Called at the end of the request lifecycle.
	 */
	function doUpdate() {
		foreach ( $this->producers as $producer ) {
			foreach ( $producer->getTasks() as $task ) {
				$this->tasks[] = $task;
			}
		}

		// Quit early if there are no tasks to be published
		if ( empty( $this->tasks ) ) {
			return;
		}

		if ( !$this->circuitBreaker->operationAllowed() ) {
			$this->info( 'circuit breaker open for task publisher' );
			return;
		}

		try {
			$channel = $this->rabbitConnectionManager->getChannel( '/' );

			while ( $task = array_pop( $this->tasks ) ) {
				$queue = $task->getQueue()->name();
				$payload = $task->serialize();

				$message = new AMQPMessage( json_encode( $payload ), [
					'content_type' => 'application/json',
					'content_encoding' => 'UTF-8',
					'immediate' => false,
					'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
					'app_id' => 'mediawiki',
					'correlation_id' => WikiaTracer::instance()->getTraceId(),
				] );

				$channel->batch_basic_publish( $message, '', $queue );

				$this->logPublish( $queue, $payload );
			}

			$channel->publish_batch();
			$channel->wait_for_pending_acks( AsyncTaskList::ACK_WAIT_TIMEOUT_SECONDS );
			$this->circuitBreaker->setOperationStatus( true );
		} catch ( AMQPExceptionInterface $e ) {
			$this->logError( $e );
		} catch ( \ErrorException $e ) {
			$this->logError( $e );
		}
	}

	private function logError( \Exception $e ) {
		$this->circuitBreaker->setOperationStatus( false );
		$this->error( 'Failed to publish background task', [
			'exception' => $e,
		] );

		return null;
	}

	private function logPublish( string $queue, array $payload ) {
		$argsJson = json_encode( $payload['args'] ?? null );

		$kwargs = $payload['kwargs']?? [];
		$task =  $payload['task'] ?? null;
		$this->info( 'Publishing task of type: ' . $task, [
			'exception' => new \Exception(),
			'spawn_task_id' => $payload['id'] ?? null,
			'spawn_task_type' => $payload['task'] ?? null,
			'spawn_task_work_id' => $kwargs ?? null,
			'spawn_task_args' => substr( $argsJson, 0, 3000 ) . ( strlen( $argsJson ) > 3000 ? '...' : '' ),
			'spawn_task_queue' => $queue,
		] );
	}
}
