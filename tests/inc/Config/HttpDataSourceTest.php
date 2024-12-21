<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Config;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Tests\Mocks\MockDataSource;

class HttpDataSourceTest extends TestCase {
	private MockDataSource $http_data_source;

	public function testGetServiceMethodCannotBeOverriddenl(): void {
		$config = [
			'service' => 'mock',
			'service_config' => [
				'endpoint' => 'http://example.com',
			],
		];
		$this->http_data_source = MockDataSource::from_array( $config );

		$this->assertSame( 'generic-http', $this->http_data_source->get_service_name() );
	}

	public function testGetServiceMethodReturnsCorrectValue(): void {
		$this->http_data_source = MockDataSource::from_array();

		$this->assertEquals( 'generic-http', $this->http_data_source->get_service_name() );
	}
}
