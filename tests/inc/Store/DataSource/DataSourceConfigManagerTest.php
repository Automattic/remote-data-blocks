<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Store\DataSource;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Store\DataSource\DataSourceConfigManager;
use RemoteDataBlocks\Store\DataSource\ConstantConfigStore;
use RemoteDataBlocks\WpdbStorage\DataSourceCrud;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use WP_Error;
use Mockery;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DataSourceConfigManagerTest extends TestCase {
	private const AIRTABLE_UUID = '90793995-b8df-40fa-9311-5033d8a9c906';
	private const SHEETS_UUID = '4b7ec9ff-1642-47d3-a5f4-009fbdcfef0e';
	private const SHOPIFY_UUID = '8f47e5c2-9388-4f3b-a9d6-e1d4c2b3a7f9';
	private const AIRTABLE_SERVICE = 'airtable';
	private const SHEETS_SERVICE = 'google-sheets';
	private const SHOPIFY_SERVICE = 'shopify';

	private array $airtable_storage_config;
	private array $sheets_constant_config;
	private array $shopify_code_config;

	protected function setUp(): void {
		parent::setUp();

		$this->airtable_storage_config = [
			'uuid' => self::AIRTABLE_UUID,
			'service' => self::AIRTABLE_SERVICE,
			'service_config' => [
				'__version' => 1,
				'enable_blocks' => true,
				'display_name' => 'Test Airtable',
				'access_token' => 'test.airtable.access-token',
				'tables' => [
					[
						'id' => 'test_table_id',
						'name' => 'Test Table',
						'output_query_mappings' => [
							[
								'path' => '$.fields["Name"]',
								'name' => 'Name',
								'key' => 'Name',
								'type' => 'string',
							],
						],
					],
				],
				'base' => [
					'id' => 'test_base_id',
					'name' => 'Test Base',
				],
			],
			'__metadata' => [
				'created_at' => '2025-02-14 11:05:36',
				'updated_at' => '2025-02-14 11:05:49',
			],
			'config_source' => DataSourceConfigManager::CONFIG_SOURCE_STORAGE,
		];

		$this->sheets_constant_config = [
			'uuid' => self::SHEETS_UUID,
			'service' => self::SHEETS_SERVICE,
			'service_config' => [
				'__version' => 1,
				'enable_blocks' => true,
				'display_name' => 'Test Google Sheets',
				'credentials' => [
					'type' => 'service_account',
					'project_id' => 'test-gcp-project',
					'private_key_id' => 'xyz987abc654def321ghi',
					'private_key' => '-----BEGIN PRIVATE KEY-----\nREDACTED\n-----END PRIVATE KEY-----\n',
					'client_email' => 'test-gcp-project@test-gcp-project.iam.gserviceaccount.com',
					'client_id' => '1234567890',
					'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
					'token_uri' => 'https://oauth2.googleapis.com/token',
					'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
					'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/test%40test-gcp-project.iam.gserviceaccount.com',
					'universe_domain' => 'googleapis.com',
				],
				'sheets' => [
					[
						'id' => '0',
						'name' => 'Test Sheet',
						'output_query_mappings' => [
							[
								'key' => 'Name',
								'name' => 'Name',
								'path' => '$["Name"]',
								'type' => 'string',
							],
						],
					],
				],
				'spreadsheet' => [
					'id' => 'some-spreadsheet-id',
					'name' => 'Test Spreadsheet',
				],
			],
			'config_source' => DataSourceConfigManager::CONFIG_SOURCE_CONSTANT,
		];

		$this->shopify_code_config = [
			'uuid' => self::SHOPIFY_UUID,
			'service' => self::SHOPIFY_SERVICE,
			'service_config' => [
				'__version' => 1,
				'access_token' => 'shpat_abc123def456ghi789jkl0',
				'store_name' => 'test-shopify-store',
				'display_name' => 'Test Shopify Store',
				'enable_blocks' => true,
			],
			'config_source' => DataSourceConfigManager::CONFIG_SOURCE_CODE,
		];
	}

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function testGetAllReturnsConfigsFromAllSources(): void {
		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'get_configs' )
			->andReturn( [ $this->airtable_storage_config ] );

		$mock_constant = Mockery::mock( 'alias:' . ConstantConfigStore::class );
		$mock_constant->shouldReceive( 'get_configs' )
			->andReturn( [ $this->sheets_constant_config ] );

		$mock_config_store = Mockery::mock( 'alias:' . ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_data_sources_as_array' )
			->andReturn( [ $this->shopify_code_config ] );

		$result = DataSourceConfigManager::get_all();
		
		$this->assertCount( 3, $result );
		$this->assertContains( $this->airtable_storage_config, $result );
		$this->assertContains( $this->sheets_constant_config, $result );
		$this->assertContains( $this->shopify_code_config, $result );
	}

	public function testGetReturnsConfigFromConstant(): void {
		$mock_constant = Mockery::mock( 'alias:' . ConstantConfigStore::class );
		$mock_constant->shouldReceive( 'get_config_by_uuid' )
			->with( self::SHEETS_UUID )
			->andReturn( $this->sheets_constant_config );

		$result = DataSourceConfigManager::get( self::SHEETS_UUID );
		$this->assertSame( $this->sheets_constant_config, $result );
	}

	public function testGetReturnsConfigFromStorage(): void {
		$mock_constant = Mockery::mock( 'alias:' . ConstantConfigStore::class );
		$mock_constant->shouldReceive( 'get_config_by_uuid' )
			->with( self::AIRTABLE_UUID )
			->andReturn( new WP_Error( 'not_found' ) );

		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'get_config_by_uuid' )
			->with( self::AIRTABLE_UUID )
			->andReturn( $this->airtable_storage_config );

		$result = DataSourceConfigManager::get( self::AIRTABLE_UUID );
		$this->assertSame( $this->airtable_storage_config, $result );
	}

	public function testGetReturnsErrorWhenConfigNotFound(): void {
		$mock_constant = Mockery::mock( 'alias:' . ConstantConfigStore::class );
		$mock_constant->shouldReceive( 'get_config_by_uuid' )
			->with( self::AIRTABLE_UUID )
			->andReturn( new WP_Error( 'not_found' ) );

		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'get_config_by_uuid' )
			->with( self::AIRTABLE_UUID )
			->andReturn( new WP_Error( 'not_found' ) );

		$result = DataSourceConfigManager::get( self::AIRTABLE_UUID );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'data_source_not_found', $result->get_error_code() );
	}

	public function testCreateReturnsNewConfig(): void {
		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'create_config' )
			->with( $this->airtable_storage_config )
			->andReturn( $this->airtable_storage_config );

		$result = DataSourceConfigManager::create( $this->airtable_storage_config );
		$this->assertSame( $this->airtable_storage_config, $result );
	}

	public function testCreateReturnsErrorOnFailure(): void {
		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'create_config' )
			->with( $this->airtable_storage_config )
			->andReturn( new WP_Error( 'create_failed' ) );

		$result = DataSourceConfigManager::create( $this->airtable_storage_config );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function testUpdateReturnsUpdatedConfig(): void {
		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'update_config_by_uuid' )
			->with( self::AIRTABLE_UUID, $this->airtable_storage_config )
			->andReturn( $this->airtable_storage_config );

		$result = DataSourceConfigManager::update( self::AIRTABLE_UUID, $this->airtable_storage_config );
		$this->assertSame( $this->airtable_storage_config, $result );
	}

	public function testUpdateReturnsErrorForImmutableConfig(): void {
		$immutable_config = array_merge( 
			$this->sheets_constant_config,
			[ 'config_source' => DataSourceConfigManager::CONFIG_SOURCE_CONSTANT ]
		);

		$result = DataSourceConfigManager::update( self::SHEETS_UUID, $immutable_config );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'cannot_update_config', $result->get_error_code() );
	}

	public function testUpdateReturnsErrorOnFailure(): void {
		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'update_config_by_uuid' )
			->with( self::AIRTABLE_UUID, $this->airtable_storage_config )
			->andReturn( new WP_Error( 'update_failed' ) );

		$result = DataSourceConfigManager::update( self::AIRTABLE_UUID, $this->airtable_storage_config );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function testDeleteReturnsTrue(): void {
		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'delete_config_by_uuid' )
			->with( self::AIRTABLE_UUID )
			->andReturn( true );

		$result = DataSourceConfigManager::delete( self::AIRTABLE_UUID );
		$this->assertTrue( $result );
	}

	public function testDeleteReturnsErrorOnFailure(): void {
		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'delete_config_by_uuid' )
			->with( self::AIRTABLE_UUID )
			->andReturn( new WP_Error( 'delete_failed' ) );

		$result = DataSourceConfigManager::delete( self::AIRTABLE_UUID );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function testGetAllHandlesConfigPrecedenceCorrectly(): void {
		// Create configs with same UUID to test precedence
		$storage_sheets = array_merge( $this->sheets_constant_config, [
			'service_config' => array_merge( $this->sheets_constant_config['service_config'], [
				'display_name' => 'Storage Sheets',
			] ),
			'config_source' => DataSourceConfigManager::CONFIG_SOURCE_STORAGE,
		] );

		$constant_sheets = array_merge( $this->sheets_constant_config, [
			'service_config' => array_merge( $this->sheets_constant_config['service_config'], [
				'display_name' => 'Constant Sheets',
			] ),
			'config_source' => DataSourceConfigManager::CONFIG_SOURCE_CONSTANT,
		] );

		$code_sheets = array_merge( $this->sheets_constant_config, [
			'service_config' => array_merge( $this->sheets_constant_config['service_config'], [
				'display_name' => 'Code Sheets',
			] ),
			'config_source' => DataSourceConfigManager::CONFIG_SOURCE_CODE,
		] );

		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'get_configs' )
			->andReturn( [ $storage_sheets ] );

		$mock_constant = Mockery::mock( 'alias:' . ConstantConfigStore::class );
		$mock_constant->shouldReceive( 'get_configs' )
			->andReturn( [ $constant_sheets ] );

		$mock_config_store = Mockery::mock( 'alias:' . ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_data_sources_as_array' )
			->andReturn( [ $code_sheets ] );

		$result = DataSourceConfigManager::get_all();

		// Should only get one config since they share the same UUID
		$this->assertCount( 1, $result );
		
		// Storage should win due to highest precedence
		$this->assertContains( $storage_sheets, $result );
		$this->assertNotContains( $constant_sheets, $result );
		$this->assertNotContains( $code_sheets, $result );
	}

	public function testGetAllWithServiceFilter(): void {
		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'get_configs' )
			->andReturn( [ $this->airtable_storage_config ] );

		$mock_constant = Mockery::mock( 'alias:' . ConstantConfigStore::class );
		$mock_constant->shouldReceive( 'get_configs' )
			->andReturn( [ $this->sheets_constant_config ] );

		$mock_config_store = Mockery::mock( 'alias:' . ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_data_sources_as_array' )
			->andReturn( [ $this->shopify_code_config ] );

		$result = DataSourceConfigManager::get_all( [ 'service' => self::AIRTABLE_SERVICE ] );
		
		$this->assertCount( 1, $result );
		$this->assertContains( $this->airtable_storage_config, $result );
	}

	public function testGetAllWithEnableBlocksFilterTrue(): void {
		// Modify one config to have enable_blocks false
		$sheets_config_blocks_disabled = array_merge_recursive( $this->sheets_constant_config, [
			'service_config' => [ 'enable_blocks' => false ],
		] );

		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'get_configs' )
			->andReturn( [ $this->airtable_storage_config ] );

		$mock_constant = Mockery::mock( 'alias:' . ConstantConfigStore::class );
		$mock_constant->shouldReceive( 'get_configs' )
			->andReturn( [ $sheets_config_blocks_disabled ] );

		$mock_config_store = Mockery::mock( 'alias:' . ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_data_sources_as_array' )
			->andReturn( [ $this->shopify_code_config ] );

		$result = DataSourceConfigManager::get_all( [ 'enable_blocks' => true ] );
		
		$this->assertCount( 2, $result );
		$this->assertContains( $this->airtable_storage_config, $result );
		$this->assertContains( $this->shopify_code_config, $result );
		$this->assertNotContains( $sheets_config_blocks_disabled, $result );
	}

	public function testGetAllWithEnableBlocksFilterFalse(): void {
		// Create config with enable_blocks not set
		$shopify_config_blocks_unset = $this->shopify_code_config;
		unset( $shopify_config_blocks_unset['service_config']['enable_blocks'] );

		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'get_configs' )
			->andReturn( [ $this->airtable_storage_config ] );

		$mock_constant = Mockery::mock( 'alias:' . ConstantConfigStore::class );
		$mock_constant->shouldReceive( 'get_configs' )
			->andReturn( [ $this->sheets_constant_config ] );

		$mock_config_store = Mockery::mock( 'alias:' . ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_data_sources_as_array' )
			->andReturn( [ $shopify_config_blocks_unset ] );

		$result = DataSourceConfigManager::get_all( [ 'enable_blocks' => false ] );
		
		$this->assertCount( 1, $result );
		$this->assertContains( $shopify_config_blocks_unset, $result );
	}

	public function testGetAllWithMultipleFilters(): void {
		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'get_configs' )
			->andReturn( [ $this->airtable_storage_config ] );

		$mock_constant = Mockery::mock( 'alias:' . ConstantConfigStore::class );
		$mock_constant->shouldReceive( 'get_configs' )
			->andReturn( [ $this->sheets_constant_config ] );

		$mock_config_store = Mockery::mock( 'alias:' . ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_data_sources_as_array' )
			->andReturn( [ $this->shopify_code_config ] );

		$result = DataSourceConfigManager::get_all( [
			'service' => self::AIRTABLE_SERVICE,
			'enable_blocks' => true,
		] );
		
		$this->assertCount( 1, $result );
		$this->assertContains( $this->airtable_storage_config, $result );
	}

	public function testGetAllReturnsEmptyWhenServiceDoesNotMatch(): void {
		// Only return configs for sheets and shopify, but search for airtable
		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'get_configs' )
			->andReturn( [ $this->sheets_constant_config ] );

		$mock_constant = Mockery::mock( 'alias:' . ConstantConfigStore::class );
		$mock_constant->shouldReceive( 'get_configs' )
			->andReturn( [ $this->shopify_code_config ] );

		$mock_config_store = Mockery::mock( 'alias:' . ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_data_sources_as_array' )
			->andReturn( [] );

		$result = DataSourceConfigManager::get_all( [ 'service' => self::AIRTABLE_SERVICE ] );
		
		$this->assertCount( 0, $result );
		$this->assertEmpty( $result );
	}

	public function testGetAllReturnsEmptyWhenEnableBlocksDoesNotMatch(): void {
		// Set all configs to have enable_blocks = true
		$airtable_config = array_merge_recursive( $this->airtable_storage_config, [
			'service_config' => [ 'enable_blocks' => true ],
		] );
		$sheets_config = array_merge_recursive( $this->sheets_constant_config, [
			'service_config' => [ 'enable_blocks' => true ],
		] );
		$shopify_config = array_merge_recursive( $this->shopify_code_config, [
			'service_config' => [ 'enable_blocks' => true ],
		] );

		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'get_configs' )
			->andReturn( [ $airtable_config ] );

		$mock_constant = Mockery::mock( 'alias:' . ConstantConfigStore::class );
		$mock_constant->shouldReceive( 'get_configs' )
			->andReturn( [ $sheets_config ] );

		$mock_config_store = Mockery::mock( 'alias:' . ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_data_sources_as_array' )
			->andReturn( [ $shopify_config ] );

		$result = DataSourceConfigManager::get_all( [ 'enable_blocks' => false ] );
		
		$this->assertCount( 0, $result );
		$this->assertEmpty( $result );
	}

	public function testGetAllReturnsEmptyWhenMultipleFiltersDoNotMatch(): void {
		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'get_configs' )
			->andReturn( [ $this->airtable_storage_config ] );

		$mock_constant = Mockery::mock( 'alias:' . ConstantConfigStore::class );
		$mock_constant->shouldReceive( 'get_configs' )
			->andReturn( [ $this->sheets_constant_config ] );

		$mock_config_store = Mockery::mock( 'alias:' . ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_data_sources_as_array' )
			->andReturn( [ $this->shopify_code_config ] );

		$result = DataSourceConfigManager::get_all( [
			'service' => self::AIRTABLE_SERVICE,
			'enable_blocks' => false,
		] );
		
		$this->assertCount( 0, $result );
		$this->assertEmpty( $result );
	}

	public function testGetAllThrowsErrorForUnsupportedFilters(): void {
		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'get_configs' )
			->andReturn( [ $this->airtable_storage_config ] );

		$mock_constant = Mockery::mock( 'alias:' . ConstantConfigStore::class );
		$mock_constant->shouldReceive( 'get_configs' )
			->andReturn( [] );

		$mock_config_store = Mockery::mock( 'alias:' . ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_data_sources_as_array' )
			->andReturn( [] );

		$result = DataSourceConfigManager::get_all( [
			'display_name' => 'Test Airtable',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_filter', $result->get_error_code() );
		$this->assertSame( 'Invalid filter key: display_name', $result->get_error_message() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	public function testGetAllWithMultipleFiltersIncludingInvalid(): void {
		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'get_configs' )
			->andReturn( [ $this->airtable_storage_config ] );

		$mock_constant = Mockery::mock( 'alias:' . ConstantConfigStore::class );
		$mock_constant->shouldReceive( 'get_configs' )
			->andReturn( [] );

		$mock_config_store = Mockery::mock( 'alias:' . ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_data_sources_as_array' )
			->andReturn( [] );

		$result = DataSourceConfigManager::get_all( [
			'service' => self::AIRTABLE_SERVICE,
			'display_name' => 'Test Airtable',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_filter', $result->get_error_code() );
		$this->assertSame( 'Invalid filter key: display_name', $result->get_error_message() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	public function testGetAllHandlesNullEnableBlocksValue(): void {
		// Create config with enable_blocks explicitly set to null
		$config_with_null_blocks = array_replace_recursive( $this->airtable_storage_config, [
			'service_config' => [ 'enable_blocks' => null ],
		] );

		$mock_storage_crud = Mockery::mock( 'alias:' . DataSourceCrud::class );
		$mock_storage_crud->shouldReceive( 'get_configs' )
			->andReturn( [ $config_with_null_blocks ] );

		$mock_constant = Mockery::mock( 'alias:' . ConstantConfigStore::class );
		$mock_constant->shouldReceive( 'get_configs' )
			->andReturn( [] );

		$mock_config_store = Mockery::mock( 'alias:' . ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_data_sources_as_array' )
			->andReturn( [] );

		// Should match when filtering for enable_blocks = false
		$result_false = DataSourceConfigManager::get_all( [ 'enable_blocks' => false ] );
		$this->assertCount( 1, $result_false );
		$this->assertContains( $config_with_null_blocks, $result_false );

		// Should not match when filtering for enable_blocks = true
		$result_true = DataSourceConfigManager::get_all( [ 'enable_blocks' => true ] );
		$this->assertCount( 0, $result_true );
		$this->assertEmpty( $result_true );
	}
}
