<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\HttpClient;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\HttpClient\RdbCacheMiddleware;
use RemoteDataBlocks\HttpClient\RdbLogMiddleware;
use RemoteDataBlocks\Tests\Mocks\MockWordPressFunctions;

class RdbLogMiddlewareTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		MockWordPressFunctions::reset();
	}

	public function testConfiguredHeaderValuesProduceDifferentLoggedCacheKeys(): void {
		$handler = static function () {
			return Create::promiseFor( new Response( 200 ) );
		};
		$log_handler = ( new RdbLogMiddleware() )( $handler );

		$first_options = [];
		$log_handler(
			new Request( 'GET', 'https://example.com/data', [
				RdbCacheMiddleware::CACHE_KEY_REQUEST_HEADERS_HEADER => [ 'X-Api-Key' ],
				'X-Api-Key' => 'first-api-key',
			] ),
			$first_options
		)->wait();

		$second_options = [];
		$log_handler(
			new Request( 'GET', 'https://example.com/data', [
				RdbCacheMiddleware::CACHE_KEY_REQUEST_HEADERS_HEADER => [ 'X-Api-Key' ],
				'X-Api-Key' => 'second-api-key',
			] ),
			$second_options
		)->wait();

		$first_log = MockWordPressFunctions::get_done_action( RdbLogMiddleware::$action_name, 0 );
		$second_log = MockWordPressFunctions::get_done_action( RdbLogMiddleware::$action_name, 1 );
		$this->assertIsArray( $first_log );
		$this->assertIsArray( $second_log );
		$this->assertNotSame( $first_log[0]['cache_key'], $second_log[0]['cache_key'] );
	}
}
