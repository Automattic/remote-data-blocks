<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Config;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Integrations\GenericHttp\GenericHttpDataSource;

class GenericHttpDataSourceTest extends TestCase {

	public function test_get_service_method_returns_correct_value(): void {
		$config = [
			'service_config' => [
				'__version' => 1,
				'display_name' => 'Mock Data Source',
				'endpoint' => 'http://example.com',
			],
		];
		$data_source = GenericHttpDataSource::from_array( $config );

		$this->assertEquals( 'generic-http', $data_source->get_service_name() );
	}

	public function test_to_array_returns_correctly_mapped_values(): void {
		$config = [
			'service_config' => [
				'__version' => 1,
				'display_name' => 'Mock Data Source',
				'endpoint' => 'http://example.com',
			],
		];

		// By calling to_array, we can ensure that the config schema is correctly defined,
		// and that values haven't been sanitized due to them missing from the schema.
		$data_source = GenericHttpDataSource::from_array( $config );
		$data_source_array = $data_source->to_array();

		$this->assertEquals( 'generic-http', $data_source_array['service'] );
		$this->assertEquals( 'http://example.com', $data_source_array['service_config']['endpoint'] );
	}
}
