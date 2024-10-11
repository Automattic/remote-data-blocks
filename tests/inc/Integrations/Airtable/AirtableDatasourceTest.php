<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Integrations\Airtable;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Integrations\Airtable\AirtableDatasource;

class AirtableDatasourceTest extends TestCase {
	private AirtableDatasource $datasource;

	protected function setUp(): void {
		parent::setUp();

		$this->datasource = AirtableDatasource::create(
			'test_access_token',
			'test_base_id',
			[],
			'Test Airtable Base'
		);
	}

	public function test_get_display_name(): void {
		$this->assertSame(
			'Airtable: Test Airtable Base',
			$this->datasource->get_display_name()
		);
	}

	public function test_get_display_name_with_base_name_override(): void {
		$datasource = AirtableDatasource::from_array([
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
			'Airtable: Test Base Name',
			$datasource->get_display_name()
		);
	}

	public function test_get_endpoint(): void {
		$this->assertSame(
			'https://api.airtable.com/v0/test_base_id',
			$this->datasource->get_endpoint()
		);
	}

	public function test_get_request_headers(): void {
		$expected_headers = [
			'Authorization' => 'Bearer test_access_token',
			'Content-Type'  => 'application/json',
		];

		$this->assertSame( $expected_headers, $this->datasource->get_request_headers() );
	}

	public function test_create(): void {
		$datasource = AirtableDatasource::create(
			'new_access_token',
			'new_base_id',
			[],
			'New Airtable Base'
		);

		$this->assertInstanceOf( AirtableDatasource::class, $datasource );
		$this->assertSame( 'Airtable: New Airtable Base', $datasource->get_display_name() );
		$this->assertSame( 'https://api.airtable.com/v0/new_base_id', $datasource->get_endpoint() );
	}
}
