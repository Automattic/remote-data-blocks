<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Config;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Tests\Mocks\MockDataSource;
use WP_Error;

class HttpDataSourceTest extends TestCase {
	public function test_migrate_config_moves_header(): void {
		$config = [
			'display_name' => 'Mock Data Source',
			'endpoint' => 'https://example.com/api',
			'request_headers' => [
				'x-user-id' => 123,
			],
		];

		$mock_data_source = MockDataSource::create( $config );

		$this->assertInstanceOf( MockDataSource::class, $mock_data_source );
		$this->assertSame( 'https://example.com/api', $mock_data_source->get_endpoint() );
		$this->assertSame( 123, $mock_data_source->get_request_headers()['x-api-user'] );
		$this->assertSame( 123, $mock_data_source->to_array()['request_headers']['x-api-user'] );
	}

	public function test_migrate_config_flags_invalid_header(): void {
		$config = [
			'display_name' => 'Mock Data Source',
			'endpoint' => 'https://example.com/api',
			'request_headers' => [
				'x-api-user' => 'not an integer',
			],
		];

		$mock_data_source = MockDataSource::create( $config );

		$this->assertInstanceOf( WP_Error::class, $mock_data_source );
		$this->assertSame( 'invalid_x_api_user', $mock_data_source->get_error_code() );
	}
}
