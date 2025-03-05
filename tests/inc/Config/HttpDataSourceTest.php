<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Config;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Tests\Mocks\MockDataSource;

class HttpDataSourceTest extends TestCase {
	private MockDataSource $http_data_source;

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

		$this->assertEquals( '123', $this->http_data_source->to_array()['service_config']['testUserId'] );
	}

	public function testMigrateConfigMethodCanBeOverridden_user_id_is_not_added_to_config(): void {
		// Migrate config should not add a testUserId to the config if it's already set.
		$config = [
			'service_config' => [
				'__version' => 1,
				'display_name' => 'Mock Data Source',
				'endpoint' => 'http://example.com',
				'testUserId' => '456',
			],
		];

		$this->http_data_source = MockDataSource::create( $config );

		$this->assertEquals( '456', $this->http_data_source->to_array()['service_config']['testUserId'] );
	}
}
