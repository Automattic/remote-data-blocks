<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Integrations\Airtable;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Integrations\Airtable\AirtableDataSource;

class AirtableDataSourceTest extends TestCase {
	private AirtableDataSource $data_source;

	protected function setUp(): void {
		parent::setUp();

		$this->data_source = AirtableDataSource::from_array( [
			'service_config' => [
				'__version' => 1,
				'access_token' => 'test_access_token',
				'display_name' => 'Airtable Source',
				'base' => [
					'id' => 'test_base_id',
					'name' => 'Test Airtable Base',
				],
				'tables' => [],
			],
		] );
	}

	public function test_get_display_name(): void {
		$this->assertSame(
			'Airtable Source',
			$this->data_source->get_display_name()
		);
	}

	public function test_get_endpoint(): void {
		$this->assertSame(
			'https://api.airtable.com/v0/test_base_id',
			$this->data_source->get_endpoint()
		);
	}

	public function test_get_request_headers(): void {
		$expected_headers = [
			'Authorization' => 'Bearer test_access_token',
			'Content-Type' => 'application/json',
		];

		$this->assertSame( $expected_headers, $this->data_source->get_request_headers() );
	}

	public function test_create(): void {
		$this->assertInstanceOf( AirtableDataSource::class, $this->data_source );
		$this->assertSame( 'Airtable Source', $this->data_source->get_display_name() );
		$this->assertSame( 'https://api.airtable.com/v0/test_base_id', $this->data_source->get_endpoint() );
	}
}
