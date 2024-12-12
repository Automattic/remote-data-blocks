<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\WpdbStorage;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\WpdbStorage\DataSourceCrud;
use WP_Error;

class DataSourceCrudTest extends TestCase {
	protected function tearDown(): void {
		clear_mocked_options();
	}

	public function test_register_new_data_source_with_valid_input() {
		$valid_source = [
			'service' => REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE,
			'service_config' => [
				'__version' => 1,
				'access_token' => 'valid_token',
				'base' => [
					'id' => 'base_id',
					'name' => 'Base Name',
				],
				'display_name' => 'Airtable Source',
				'tables' => [],
			],
		];

		$result = DataSourceCrud::create_config( $valid_source );

		$this->assertIsArray( $result );
		$this->assertSame( REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE, $result['service'] );
		$this->assertTrue( wp_is_uuid( $result['uuid'] ) );
	}

	public function test_register_new_data_source_with_invalid_input() {
		$invalid_source = [
			'service' => 'unsupported',
			'service_config' => [],
			'uuid' => wp_generate_uuid4(),
		];

		// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		set_error_handler(static function ( int $errno, string $errstr ): never {
			throw new \Exception( $errstr, $errno );
		}, E_USER_WARNING);
		// phpcs:enable

		$result = DataSourceCrud::create_config( $invalid_source );
		restore_error_handler();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertsame( 'unsupported_data_source', $result->get_error_code() );
	}

	public function test_get_configs() {
		$source1 = DataSourceCrud::create_config( [
			'service' => REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE,
			'service_config' => [
				'__version' => 1,
				'access_token' => 'token1',
				'display_name' => 'Airtable Source',
				'base' => [
					'id' => 'base_id1',
					'name' => 'Base Name 1',
				],
				'tables' => [],
			],
			'uuid' => wp_generate_uuid4(),
		] );

		$source2 = DataSourceCrud::create_config( [
			'service' => REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE,
			'service_config' => [
				'__version' => 1,
				'access_token' => 'token2',
				'display_name' => 'Shopify Source',
				'store_name' => 'mystore',
			],
			'uuid' => wp_generate_uuid4(),
		] );

		$all_sources = DataSourceCrud::get_configs();
		$this->assertCount( 2, $all_sources );

		$airtable_sources = DataSourceCrud::get_configs_by_service( 'airtable' );
		$this->assertCount( 1, $airtable_sources );
		$this->assertSame( 'token1', $airtable_sources[0]['service_config']['access_token'] );
		$this->assertSame( $source1['uuid'], $airtable_sources[0]['uuid'] );

		$shopify_sources = DataSourceCrud::get_configs_by_service( 'shopify' );
		$this->assertCount( 1, $shopify_sources );
		$this->assertSame( 'mystore', $shopify_sources[0]['service_config']['store_name'] );
		$this->assertSame( $source2['uuid'], $shopify_sources[0]['uuid'] );
	}

	public function test_get_item_by_uuid_with_valid_uuid() {
		$source = DataSourceCrud::create_config( [
			'service' => REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE,
			'service_config' => [
				'__version' => 1,
				'access_token' => 'token1',
				'base' => [
					'id' => 'base_id1',
					'name' => 'Base Name 1',
				],
				'display_name' => 'Airtable Source',
				'tables' => [],
			],
			'uuid' => wp_generate_uuid4(),
		] );

		$retrieved_source = DataSourceCrud::get_config_by_uuid( $source['uuid'] );
		$this->assertArrayHasKey( '__metadata', $retrieved_source );
		$this->assertArrayHasKey( 'created_at', $retrieved_source['__metadata'] );
		$this->assertArrayHasKey( 'updated_at', $retrieved_source['__metadata'] );
	}

	public function test_get_item_by_uuid_with_invalid_uuid() {
		$non_existent = DataSourceCrud::get_config_by_uuid( 'non-existent-uuid' );
		$this->assertInstanceOf( WP_Error::class, $non_existent );
		$this->assertsame( 'data_source_not_found', $non_existent->get_error_code() );
	}

	public function test_update_item_by_uuid_with_valid_uuid() {
		$source = DataSourceCrud::create_config( [
			'service' => REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE,
			'service_config' => [
				'__version' => 1,
				'access_token' => 'token1',
				'base' => [
					'id' => 'base_id1',
					'name' => 'Base Name 1',
				],
				'display_name' => 'Airtable Source',
				'tables' => [],
			],
			'uuid' => wp_generate_uuid4(),
		] );

		$updated_source = DataSourceCrud::update_config_by_uuid( $source['uuid'], [
			'access_token' => 'updated_token',
		] );

		$this->assertIsArray( $updated_source );
		$this->assertSame( 'updated_token', $updated_source['service_config']['access_token'] );
	}

	public function test_update_item_by_uuid_with_invalid_uuid() {
		$non_existent = DataSourceCrud::update_config_by_uuid( 'non-existent-uuid', [ 'token' => 'new_token' ] );
		$this->assertInstanceOf( WP_Error::class, $non_existent );
		$this->assertSame( 'data_source_not_found', $non_existent->get_error_code() );
	}

	public function test_delete_item_by_uuid() {
		$source = DataSourceCrud::create_config( [
			'service' => REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE,
			'service_config' => [
				'__version' => 1,
				'access_token' => 'token1',
				'base' => [
					'id' => 'base_id1',
					'name' => 'Base Name 1',
				],
				'display_name' => 'Airtable Source',
				'tables' => [],
			],
			'uuid' => wp_generate_uuid4(),
		] );

		$result = DataSourceCrud::delete_config_by_uuid( $source['uuid'] );
		$this->assertTrue( $result );

		$deleted_source = DataSourceCrud::get_config_by_uuid( $source['uuid'] );
		$this->assertInstanceOf( WP_Error::class, $deleted_source );
		$this->assertSame( 'data_source_not_found', $deleted_source->get_error_code() );
	}

	public function test_get_by_uuid_with_non_existent_uuid() {
		$non_existent = DataSourceCrud::get_config_by_uuid( '64af9297-867e-4e39-b51d-7c97beeebec6' );
		$this->assertInstanceOf( WP_Error::class, $non_existent );
		$this->assertSame( 'data_source_not_found', $non_existent->get_error_code() );
	}
}
