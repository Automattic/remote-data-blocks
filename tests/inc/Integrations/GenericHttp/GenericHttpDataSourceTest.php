<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Config;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Integrations\GenericHttp\GenericHttpDataSource;

class GenericHttpDataSourceTest extends TestCase {
	public function testGetServiceMethodReturnsCorrectValue(): void {
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
}
