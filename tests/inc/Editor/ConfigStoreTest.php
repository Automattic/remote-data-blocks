<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Editor\BlockPatterns;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\Integrations\Airtable\AirtableDataSource;

class ConfigStoreTest extends TestCase {
	public function testGetDataSourceReturnsNullIfConfigIsNotFound(): void {
		ConfigStore::init();

		$this->assertNull( ConfigStore::get_data_source_type( 'block_name' ) );
	}

	public function testGetDataSourceReturnsNullIfThereAreNoQueries(): void {
		ConfigStore::init();
		ConfigStore::set_block_configuration( 'block_name', [ 'queries' => [] ] );

		$this->assertNull( ConfigStore::get_data_source_type( 'block_name' ) );
	}

	public function testGetDataSourceReturnsDataSource(): void {
		ConfigStore::init();
		ConfigStore::set_block_configuration( 'airtable_remote_blocks', [
			'queries' => [
				'display' => HttpQuery::from_array( [
					'data_source' => AirtableDataSource::from_array( [
						'service_config' => [
							'__version' => 1,
							'access_token' => 'token',
							'base' => [
								'id' => 'foo',
							],
							'display_name' => 'Name',
							'tables' => [],
						],
					] ),
					'output_schema' => [ 'type' => 'string' ],
				] ),
			],
		] );

		$this->assertEquals( 'airtable', ConfigStore::get_data_source_type( 'airtable_remote_blocks' ) );
	}
}
