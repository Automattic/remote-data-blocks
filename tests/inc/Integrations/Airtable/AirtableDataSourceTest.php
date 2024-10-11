<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Integrations\Airtable;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Integrations\Airtable\AirtableDataSource;

class AirtableDataSourceTest extends TestCase {
	private AirtableDataSource $data_source;

	protected function setUp(): void {
		parent::setUp();

		$this->data_source = AirtableDataSource::create(
			'test_access_token',
			'test_base_id',
			[],
			'Test Airtable Base'
		);
	}

	public function test_get_display_name(): void {
		$this->assertSame(
			'Airtable (Test Airtable Base)',
			$this->data_source->get_display_name()
		);
	}

	public function test_get_display_name_with_base_name_override(): void {
		$data_source = AirtableDataSource::from_array([
			'service'      => REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE,
			'access_token' => 'test_access_token',
			'base'         => [
				'id'   => 'test_base_id',
				'name' => 'Test Base Name',
			],
			'tables'       => [],
			'slug'         => 'test-airtable-base',
		]);

		$this->assertSame(
			'Airtable (test-airtable-base)',
			$data_source->get_display_name()
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
			'Content-Type'  => 'application/json',
		];

		$this->assertSame( $expected_headers, $this->data_source->get_request_headers() );
	}

	public function test_create(): void {
		$data_source = AirtableDataSource::create(
			'new_access_token',
			'new_base_id',
			[],
			'New Airtable Base'
		);

		$this->assertInstanceOf( AirtableDataSource::class, $data_source );
		$this->assertSame( 'Airtable (New Airtable Base)', $data_source->get_display_name() );
		$this->assertSame( 'https://api.airtable.com/v0/new_base_id', $data_source->get_endpoint() );
	}
}
