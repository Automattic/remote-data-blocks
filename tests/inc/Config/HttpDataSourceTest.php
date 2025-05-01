<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Config;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Tests\Mocks\MockDataSource;
use WP_Error;

class HttpDataSourceTest extends TestCase {
	private MockDataSource|WP_Error $http_data_source;

	public function testGetServiceMethodCannotBeOverridden(): void {
		$config = [
			'service_config' => [
				'__version' => 1,
				'display_name' => 'Mock Data Source',
				'endpoint' => 'http://example.com',
			],
		];
		$this->http_data_source = MockDataSource::create( $config );

		$this->assertSame( 'generic-http', $this->http_data_source->get_service_name() );
	}

	public function testGetServiceMethodReturnsCorrectValue(): void {
		$this->http_data_source = MockDataSource::create();

		$this->assertEquals( 'generic-http', $this->http_data_source->get_service_name() );
	}

	public function testMigrateConfigMethodCanBeOverridden_user_id_is_added_to_config(): void {
		// Migrate config should add a testUserId to the config if it's not already set.
		$this->http_data_source = MockDataSource::create();

		$this->assertEquals( 1, $this->http_data_source->to_array()['service_config']['testUserId'] );
	}

	public function testMigrateConfigMethodCanBeOverridden_user_id_is_not_added_to_config(): void {
		// Migrate config should not add a testUserId to the config if it's already set.
		$config = [
			'service_config' => [
				'__version' => 1,
				'display_name' => 'Mock Data Source',
				'endpoint' => 'http://example.com',
				'testUserId' => 2,
			],
		];

		$this->http_data_source = MockDataSource::create( $config );

		$this->assertEquals( 2, $this->http_data_source->to_array()['service_config']['testUserId'] );
	}

	public function testMigrateConfigMethodCanBeOverridden_user_id_is_not_an_integer(): void {
		// Migrate config should trigger an error as the testUserId is not an integer.
		$config = [
			'service_config' => [
				'__version' => 1,
				'display_name' => 'Mock Data Source',
				'endpoint' => 'http://example.com',
				'testUserId' => 'not an integer',
			],
		];

		$this->http_data_source = MockDataSource::create( $config );

		$this->assertInstanceOf( WP_Error::class, $this->http_data_source );
		$this->assertSame( 'testUserId must be an integer', $this->http_data_source->get_error_message() );
	}

	public function testBearerAuthHeaderIsAdded(): void {
		$config = [
			'service_config' => [
				'__version' => 1,
				'display_name' => 'Mock Data Source',
				'endpoint' => 'http://example.com',
				'auth' => [
					'type' => 'bearer',
					'value' => 'test-token',
				],
			],
		];
		$this->http_data_source = MockDataSource::create( $config );

		$this->assertNotInstanceOf( WP_Error::class, $this->http_data_source );
		$headers = $this->http_data_source->get_request_headers();
		$this->assertIsArray( $headers );
		$this->assertArrayHasKey( 'Authorization', $headers );
		$this->assertSame( 'Bearer test-token', $headers['Authorization'] );
	}

	public function testBasicAuthHeaderIsAdded(): void {
		$config = [
			'service_config' => [
				'__version' => 1,
				'display_name' => 'Mock Data Source',
				'endpoint' => 'http://example.com',
				'auth' => [
					'type' => 'basic',
					'value' => 'user:pass',
				],
			],
		];
		$this->http_data_source = MockDataSource::create( $config );

		$this->assertNotInstanceOf( WP_Error::class, $this->http_data_source );
		$headers = $this->http_data_source->get_request_headers();
		$this->assertIsArray( $headers );
		$this->assertArrayHasKey( 'Authorization', $headers );
		$this->assertSame( 'Basic ' . base64_encode( 'user:pass' ), $headers['Authorization'] );
	}

	public function testApiKeyHeaderIsAdded(): void {
		$config = [
			'service_config' => [
				'__version' => 1,
				'display_name' => 'Mock Data Source',
				'endpoint' => 'http://example.com',
				'auth' => [
					'type' => 'api-key',
					'add_to' => 'header',
					'key' => 'X-Api-Key',
					'value' => 'test-api-key',
				],
			],
		];
		$this->http_data_source = MockDataSource::create( $config );

		$this->assertNotInstanceOf( WP_Error::class, $this->http_data_source );
		$headers = $this->http_data_source->get_request_headers();
		$this->assertIsArray( $headers );
		$this->assertArrayHasKey( 'X-Api-Key', $headers );
		$this->assertSame( 'test-api-key', $headers['X-Api-Key'] );
	}

	public function testApiKeyQueryParamIsAdded(): void {
		$config = [
			'service_config' => [
				'__version' => 1,
				'display_name' => 'Mock Data Source',
				'endpoint' => 'http://example.com/data',
				'auth' => [
					'type' => 'api-key',
					'add_to' => 'queryparams',
					'key' => 'api_key',
					'value' => 'test-api-key',
				],
			],
		];
		$this->http_data_source = MockDataSource::create( $config );

		$this->assertNotInstanceOf( WP_Error::class, $this->http_data_source );
		$endpoint = $this->http_data_source->get_endpoint();
		$this->assertSame( 'http://example.com/data?api_key=test-api-key', $endpoint );
	}

	public function testApiKeyQueryParamIsAppended(): void {
		$config = [
			'service_config' => [
				'__version' => 1,
				'display_name' => 'Mock Data Source',
				'endpoint' => 'http://example.com/data?existing=true',
				'auth' => [
					'type' => 'api-key',
					'add_to' => 'queryparams',
					'key' => 'api_key',
					'value' => 'test-api-key',
				],
			],
		];
		$this->http_data_source = MockDataSource::create( $config );

		$this->assertNotInstanceOf( WP_Error::class, $this->http_data_source );
		$endpoint = $this->http_data_source->get_endpoint();
		$this->assertSame( 'http://example.com/data?existing=true&amp;api_key=test-api-key', $endpoint );
	}

	public function testNoAuthLeavesConfigUnchanged(): void {
		$config = [
			'service_config' => [
				'__version' => 1,
				'display_name' => 'Mock Data Source',
				'endpoint' => 'http://example.com/data',
				'request_headers' => [ 'X-Custom' => 'value' ],
			],
		];
		$this->http_data_source = MockDataSource::create( $config );

		$this->assertNotInstanceOf( WP_Error::class, $this->http_data_source );
		$headers = $this->http_data_source->get_request_headers();
		$endpoint = $this->http_data_source->get_endpoint();

		$this->assertIsArray( $headers );
		$this->assertSame( [ 'X-Custom' => 'value' ], $headers );
		$this->assertSame( 'http://example.com/data', $endpoint );
	}

	public function testExistingHeadersAreMergedWithAuthHeaders(): void {
		$config = [
			'service_config' => [
				'__version' => 1,
				'display_name' => 'Mock Data Source',
				'endpoint' => 'http://example.com',
				'request_headers' => [ 'X-Custom' => 'value' ],
				'auth' => [
					'type' => 'bearer',
					'value' => 'test-token',
				],
			],
		];
		$this->http_data_source = MockDataSource::create( $config );

		$this->assertNotInstanceOf( WP_Error::class, $this->http_data_source );
		$headers = $this->http_data_source->get_request_headers();
		$this->assertIsArray( $headers );
		$this->assertSame(
			[
				'X-Custom' => 'value',
				'Authorization' => 'Bearer test-token',
			],
			$headers
		);
	}
}
