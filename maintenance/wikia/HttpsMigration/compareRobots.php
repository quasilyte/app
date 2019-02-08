<?php
require_once __DIR__ . '/../../Maintenance.php';

ini_set( 'display_errors', 'stderr' );
ini_set( 'error_reporting', E_NOTICE );

/**
 * Script to compare robots response between MW/FC and robots service.
 *
 * This script runs against production wikis (should be run from prod server like cron-s1).
 */
class CompareRobots extends Maintenance {

	private $batchSize = 1000;
	const TIMEOUT = 10;	// seconds

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Compares the response of robots service and MW/FC';
		$this->addOption( 'diffsdir', 'Directory where differences in the responses are saved',
			false, true, 'd' );
		$this->addOption( 'staging', 'Staging env for service requests', false, true );
		$this->addOption( 'fc', 'Check only FC communities', false, false );
	}

	private function httpGet( $url, $headers, $ssl, $external = false ) {
		if ( !$external ) {
			$headers['Fastly-FF'] = 1;
			if ( $ssl ) {
				$headers['Fastly-SSL'] = 1;
			}
		} else {
			if ( $ssl ) {
				$url = str_replace( 'http://', 'https://', $url );
			} else {
				$url = str_replace( 'https://', 'http://', $url );
			}
		}
		$options = [
			'returnInstance' => true,
			'followRedirects' => false,
			'timeout' => self::TIMEOUT,
			'headers' => $headers,
		];
		if ( !$external ) {
			$response = \Http::get( $url, self::TIMEOUT, $options );
		} else {
			$response = \ExternalHttp::get( $url, self::TIMEOUT, $options );

		}
		return $response;
	}

	private function fetchRobotsFromMediaWiki( $domain, $ssl = false ) {
		$url = $domain . '/robots.txt';

		return $this->httpGet( $url, [], $ssl );
	}

	private function fetchRobotsFromFC( $domain, $ssl = false ) {
		/*
		 $headers = [
			'X-Original-Host' => parse_url( $domain, PHP_URL_HOST ),
		];

		return $this->httpGet( 'http://graph-cms.fandom.com/robots.txt', $headers, $ssl );
		*/
		$url = $domain . '/robots.txt';
		return $this->httpGet( $url, [], $ssl, true );

	}

	private function fetchRobotsFromK8s(
		$domain, $ssl = false, $jaegerDebugId = false, $staging = ''
	) {
		$url = $domain . '/newrobots.txt';
		$headers = [];
		if ( $jaegerDebugId ) {
			$headers['jaeger-debug-id'] = $jaegerDebugId;
		}
		if ( $staging ) {
			$headers['X-Staging'] = $staging;
			$url .= '?forcerobots=1';
		}

		return $this->httpGet( $url, $headers, $ssl );
	}

	private function responsesEqual( $prodResponse, $serviceResponse, $staging ) {
		if ( $prodResponse->getStatus() !== $serviceResponse->getStatus() ) {
			$this->error( "\tFAILURE: Statuses don't match, prod: {$prodResponse->getStatus()}, service: {$serviceResponse->getStatus()}" );

			return false;
		}
		if ( $prodResponse->getStatus() >= 300 && $prodResponse->getStatus() < 400 ) {
			$prodRedirect = $prodResponse->getResponseHeader( 'location' );
			$serviceRedirect = $serviceResponse->getResponseHeader( 'location' );
			$serviceRedirect = str_replace( 'newrobots.txt', 'robots.txt', $serviceRedirect );
			$serviceRedirect = str_replace( '?forcerobots=1', '', $serviceRedirect );
			if ( $staging ) {
				$serviceRedirect = str_replace( ".{$staging}.", '.', $serviceRedirect );
			}
			if ( $prodRedirect !== $serviceRedirect ) {
				$this->error( "\tFAILURE: Redirects don't match, prod: {$prodRedirect}, service: {$serviceRedirect}" );

				return false;
			}

			return true;
		}
		if ( $prodResponse->getStatus() > 400 && $prodResponse->getStatus() <= 410 ) {
			return true;
		}
		if ( $prodResponse->getStatus() != 200 ) {
			$this->error( "\tFAILURE: Invalid prod response status: {$prodResponse->getStatus()}" );

			return false;
		}
		$prodContent = preg_replace( "/\n\n+/s", "\n", $prodResponse->getContent() );
		$serviceContent = preg_replace( "/\n\n+/s", "\n", $serviceResponse->getContent() );
		$prodContent = str_replace( ': ', ':', $prodContent );
		$serviceContent = str_replace( ': ', ':', $serviceContent );
		if ( $staging ) {
			// remove staging-specific responses
			$serviceContent = str_replace( ".{$staging}.", '.', $serviceContent );
		}

		if ( $prodContent !== $serviceContent ) {
			$this->error( "\tFAILURE: Reponse content is different" );

			return false;
		}

		return true;
	}

	private function dumpResponse( $filename, $response ) {
		$file = fopen( $filename, "w" );
		if ( !$file ) {
			$this->error( "\tCannot create log file" );

			return;
		}
		fwrite( $file, "Status: status: {$response->getStatus()}\n" );
		fwrite( $file, "==== HEADERS ====\n" );
		fwrite( $file, print_r( $response->getResponseHeaders(), true ) );
		fwrite( $file, "\n==== CONTENT ====\n" );
		fwrite( $file, $response->getContent() );
		fclose( $file );
	}

	public function execute() {
		global $wgHTTPProxy;
		$wgHTTPProxy = 'prod.border.service.sjc.consul:80';

		$diffsdir = $this->getOption( 'diffsdir', false );
		if ( $diffsdir ) {
			$diffsdir = rtrim( $diffsdir, '/' );
			if ( !is_dir( $diffsdir ) ) {
				$this->error( "Directory $diffsdir does not exists, ignoring\n" );
				$diffsdir = null;
			}
		}
		$staging = $this->getOption( 'staging', false );
		$fcOnly = $this->getOption( 'fc', false );

		$db = wfGetDB( DB_SLAVE, [], 'wikicities' );

		$fcCommunities =
			array_reduce( WikiFactory::getVariableForAllWikis( FandomCreator\CommunitySetup::WF_VAR_FC_COMMUNITY_ID,
				1000000 ), function ( $result, $item ) {
				if ( $item['value'] != - 1 ) {
					$result[$item['city_id']] = $item['value'];
				}

				return $result;
			}, [] );
		$lastCityId = 0;
		$domainsChecked = 0;
		$failures = 0;

		do {
			$cities =
				$db->select( 'city_list', [ 'city_id', 'city_url' ], "city_id > $lastCityId",
					__METHOD__, [ 'ORDER BY' => 'city_id ASC', 'LIMIT' => $this->batchSize ] );
			while ( $wiki = $cities->fetchObject() ) {
				$lastCityId = $wiki->city_id;
				if ( $fcOnly && !array_key_exists( $wiki->city_id, $fcCommunities ) ) {
					continue;
				}
				$parsed = parse_url( $wiki->city_url );
				if ( $parsed['path'] != '/' ) {
					// don't compare rebots for language path wikis
					continue;
				}
				$url = $parsed['scheme'] . '://' . $parsed['host'];
				foreach ( [ false, true ] as $https ) {
					$this->output( "Checking {$url} domain, https: " . json_encode( $https ) .
								   "\n" );
					if ( !array_key_exists( $wiki->city_id, $fcCommunities ) ) {
						$prodResponse = $this->fetchRobotsFromMediaWiki( $url, $https );
					} else {
						$prodResponse = $this->fetchRobotsFromFC( $url, $https );
						$servedBy = $prodResponse->getResponseHeaders()['x-served-by'];
						if ( is_array( $servedBy ) && strpos( $servedBy[0], 'ap-s' ) === 0 ) {
							$this->output( "\tSkipping this FC community as it seems to be served by MW\n" );
							continue;
						}
					}
					$jaegerDebugId = sprintf( "%04d%06d", rand( 1000, 9999 ), $domainsChecked );

					$serviceResponse =
						$this->fetchRobotsFromK8s( $url, $https, $jaegerDebugId, $staging );

					$domainsChecked += 1;

					if ( !$this->responsesEqual( $prodResponse, $serviceResponse, $staging ) ) {
						if ( $serviceResponse->getStatus() >= 500 ||
							 !$serviceResponse->status->isOK() ) {
							$this->error( "\tjaeger-debug-id: {$jaegerDebugId}, time: " .
										  date( 'Y/m/d H:i:s', time() ) );
							$this->error( "\t" . json_encode(
								$serviceResponse->status->getErrorsArray() ) . "\n" );
						}
						$failures += 1;
						if ( $diffsdir ) {
							$filename =
								str_replace( '.', '_', $parsed['host'] ) . '_' .
								( $https ? 'https' : 'http' );
							$prodFile = join( '/', [ $diffsdir, $filename . '_prod.txt' ] );
							$serviceFile = join( '/', [ $diffsdir, $filename . '_service.txt' ] );
							$this->dumpResponse( $prodFile, $prodResponse );
							$this->dumpResponse( $serviceFile, $serviceResponse );
						}
					} else {
						$this->output( "\tSUCCESS!\n" );
					}
				}
			}
			$rowsCount = $cities->numRows();
			$db->freeResult( $cities );
		} while ( $rowsCount == $this->batchSize );

		$this->output( "Total domains checked: {$domainsChecked}, failures: {$failures}" );
	}
}

$maintClass = 'CompareRobots';
require_once RUN_MAINTENANCE_IF_MAIN;
