<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Editor\BlockPatterns;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\Integrations\Airtable\AirtableDataSource;

class ConfigStoreTest extends TestCase {
	public function testGetDataSourceReturnsNullIfConfigIsNotFound(): void {
		ConfigStore::init();

		$this->assertNull( ConfigStore::get_data_source_type( 'block_name' ) );
	}

	public function testGetDataSourceReturnsNullIfThereAreNoQueries(): void {
		ConfigStore::init();
		ConfigStore::set_configuration( 'block_name', [ 'queries' => [] ] );

		$this->assertNull( ConfigStore::get_data_source_type( 'block_name' ) );
	}

	public function testGetDataSourceReturnsDataSource(): void {
		ConfigStore::init();
		ConfigStore::set_configuration( 'airtable_remote_blocks', [
			'queries' => [ new HttpQueryContext( AirtableDataSource::create( 'access_token', 'base_id', [], 'Name' ) ) ],
		] );

		$this->assertEquals( 'airtable', ConfigStore::get_data_source_type( 'airtable_remote_blocks' ) );
	}
}
