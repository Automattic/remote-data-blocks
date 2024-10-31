<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Config;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Tests\Mocks\MockDataSource;
use RemoteDataBlocks\Tests\Mocks\MockValidator;

class HttpDataSourceTest extends TestCase {
	private MockDataSource $http_data_source;

	public function testGetServiceMethodReturnsNull(): void {
		$this->http_data_source = MockDataSource::from_array( [], new MockValidator() );

		$this->assertNull( $this->http_data_source->get_service() );
	}

	public function testGetServiceMethodReturnsCorrectValue(): void {
		$this->http_data_source = MockDataSource::from_array( MockDataSource::MOCK_CONFIG, new MockValidator() );

		$this->assertEquals( 'mock', $this->http_data_source->get_service() );
	}
}
