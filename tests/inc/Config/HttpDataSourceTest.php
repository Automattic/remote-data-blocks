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
}
