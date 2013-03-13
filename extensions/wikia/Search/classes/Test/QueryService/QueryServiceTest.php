<?php
/**
 * Class definition for Wikia\Search\Test\QueryServiceTest
 */
namespace Wikia\Search\Test\QueryService;
use ReflectionProperty, ReflectionMethod, Wikia\Search;
/**
 * Responsible for testing DependencyContainer and Factory
 */
class QueryServiceTest extends Search\Test\BaseTest {
	
	/**
	 * @covers Wikia\Search\QueryService\DependencyContainer::__construct
	 * @covers Wikia\Search\QueryService\DependencyContainer::getInterface
	 * @covers Wikia\Search\QueryService\DependencyContainer::setInterface
	 * @covers Wikia\Search\QueryService\DependencyContainer::getConfig
	 * @covers Wikia\Search\QueryService\DependencyContainer::setConfig
	 * @covers Wikia\Search\QueryService\DependencyContainer::getClient
	 * @covers Wikia\Search\QueryService\DependencyContainer::setClient
	 * @covers Wikia\Search\QueryService\DependencyContainer::getResultSetFactory
	 * @covers Wikia\Search\QueryService\DependencyContainer::setResultSetFactory
	 */
	public function testDependencyContainer() {
		$mockClient = $this->getMockBuilder( '\Solarium_Client' )
		                   ->disableOriginalConstructor()
		                   ->getMock();
		
		$config = new Search\Config();
		$interface = new Search\MediaWikiService;
		$factory = new Search\ResultSet\Factory;
		$dc = new Search\QueryService\DependencyContainer( array() );
		$dc->setInterface( $interface )
		   ->setResultSetFactory( $factory )
		   ->setConfig( $config )
		   ->setClient( $mockClient );
		$this->assertEquals(
				$mockClient,
				$dc->getClient()
		);
		$this->assertEquals(
				$config,
				$dc->getConfig()
		);
		$this->assertEquals(
				$interface,
				$dc->getInterface()
		);
		$this->assertEquals(
				$factory,
				$dc->getResultSetFactory()
		);
	}
	
	/**
	 * @covers Wikia\Search\QueryService\Factory::get
	 */
	public function testFactoryGet() {
		$mockConfig = $this->getMockBuilder( 'Wikia\Search\Config' )
		                   ->setMethods( array( 'isInterWiki', 'getVideoSearch', 'getDirectLuceneQuery' ) )
		                   ->getMock();
		
		$mockFactory = $this->getMockBuilder( 'Wikia\Search\QueryService\Factory' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'validateClient' ) )
		                    ->getMock();
		
		$dc = new Search\QueryService\DependencyContainer( array( 'config' => $mockConfig ) );
		
		$mockFactory
		    ->expects( $this->atLeastOnce() )
		    ->method ( 'validateClient' )
		    ->with   ( $dc )
		;
		$mockConfig
		    ->expects( $this->at( 0 ) )
		    ->method ( 'isInterWiki' )
		    ->will   ( $this->returnValue( true ) )
		;
		$this->assertInstanceOf(
				'Wikia\Search\QueryService\Select\InterWiki',
				$mockFactory->get( $dc )
		);
		$mockConfig
		    ->expects( $this->at( 0 ) )
		    ->method ( 'isInterWiki' )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getVideoSearch' )
		    ->will   ( $this->returnValue( true ) )
		;
		$this->assertInstanceOf(
				'Wikia\Search\QueryService\Select\Video',
				$mockFactory->get( $dc )
		);
		$mockConfig
		    ->expects( $this->at( 0 ) )
		    ->method ( 'isInterWiki' )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getVideoSearch' )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->at( 2 ) )
		    ->method ( 'getDirectLuceneQuery' )
		    ->will   ( $this->returnValue( true ) )
		;
		$this->assertInstanceOf(
				'Wikia\Search\QueryService\Select\Lucene',
				$mockFactory->get( $dc )
		);
		$mockConfig
		    ->expects( $this->at( 0 ) )
		    ->method ( 'isInterWiki' )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getVideoSearch' )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->at( 2 ) )
		    ->method ( 'getDirectLuceneQuery' )
		    ->will   ( $this->returnValue( false ) )
		;
		$this->assertInstanceOf(
				'Wikia\Search\QueryService\Select\OnWiki',
				$mockFactory->get( $dc )
		);
	}
	
	/**
	 * @covers Wikia\Search\QueryService\Factory::getFromConfig
	 */
	public function testFactoryGetFromConfig() {
		$config = new Search\Config();
		$this->assertInstanceOf(
				'Wikia\Search\QueryService\Select\AbstractSelect',
				(new Search\QueryService\Factory)->getFromConfig( $config )
		);
	}
	
	/**
	 * @covers Wikia\Search\QueryService\Factory::validateClient
	 */
	public function testFactoryValidateClient() {
		$dc = new Search\QueryService\DependencyContainer( array() );
		$factory = new Search\QueryService\Factory;
		$reflValidate = new ReflectionMethod( 'Wikia\Search\QueryService\Factory' ,'validateClient' );
		$reflValidate->setAccessible( true );
		$reflValidate->invoke( $factory, $dc );
		$this->assertInstanceOf(
				'\Solarium_Client',
				$dc->getClient()
		);
	}
	
}
