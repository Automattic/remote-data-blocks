<?php

namespace RemoteDataBlocks\Tests\WpdbStorage;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\Datasource\HttpDatasource;
use RemoteDataBlocks\WpdbStorage\DatasourceCrud;
use WP_Error;

class DatasourceCrudTest extends TestCase {
	protected function tearDown(): void {
		clear_mocked_options();
	}

	public function test_register_new_data_source_with_valid_input() {
		$valid_source = [
			'access_token' => 'valid_token',
			'service'      => 'airtable',
			'base'         => [
				'id'   => 'base_id',
				'name' => 'Base Name',
			],
			'tables'       => [
				'id'   => 'table_id',
				'name' => 'Table Name',
			],
			'slug'         => 'valid-slug',
		];

		$result = DatasourceCrud::register_new_data_source( $valid_source );

		var_dump( $result );
		$this->assertInstanceOf( HttpDatasource::class, $result );
		$this->assertTrue( wp_is_uuid( $result->to_array()['uuid'] ) );
	}

	public function test_register_new_data_source_with_invalid_input() {
		$invalid_source = [
			'service' => 'unsupported',
			'slug'    => 'valid-slug',
		];

		// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		set_error_handler(static function ( int $errno, string $errstr ): never {
			throw new \Exception( $errstr, $errno );
		}, E_USER_WARNING);
		// phpcs:enable

		$result = DatasourceCrud::register_new_data_source( $invalid_source );
		restore_error_handler();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'unsupported_datasource', $result->get_error_code() );
	}

	public function test_get_data_sources() {
		$source1 = DatasourceCrud::register_new_data_source( [
			'access_token' => 'token1',
			'service'      => 'airtable',
			'base'         => [
				'id'   => 'base_id1',
				'name' => 'Base Name 1',
			],
			'tables'       => [
				'id'   => 'table_id1',
				'name' => 'Table Name 1',
			],
			'slug'         => 'source-1',
		] );

		$source2 = DatasourceCrud::register_new_data_source( [
			'access_token' => 'token2',
			'service'      => 'shopify',
			'store_name'   => 'mystore',
			'slug'         => 'source-2',
		] );

		set_mocked_option( DatasourceCrud::CONFIG_OPTION_NAME, [
			$source1->to_array(),
			$source2->to_array(),
		] );

		$all_sources = DatasourceCrud::get_data_sources();
		$this->assertCount( 2, $all_sources );

		$airtable_sources = DatasourceCrud::get_data_sources( 'airtable' );
		$this->assertCount( 1, $airtable_sources );
		$this->assertSame( 'source-1', $airtable_sources[0]['slug'] );

		$shopify_sources = DatasourceCrud::get_data_sources( 'shopify' );
		$this->assertCount( 1, $shopify_sources );
		$this->assertSame( 'source-2', $shopify_sources[0]['slug'] );
	}

	public function test_get_item_by_uuid_with_valid_uuid() {
		$source = DatasourceCrud::register_new_data_source( [
			'access_token' => 'token1',
			'service'      => 'airtable',
			'base'         => [
				'id'   => 'base_id1',
				'name' => 'Base Name 1',
			],
			'tables'       => [
				'id'   => 'table_id1',
				'name' => 'Table Name 1',
			],
			'slug'         => 'source-1',
		] );

		$retrieved_source = DatasourceCrud::get_item_by_uuid( DatasourceCrud::get_data_sources(), $source->to_array()['uuid'] );
		$this->assertSame( $source->to_array(), $retrieved_source );
	}

	public function test_get_item_by_uuid_with_invalid_uuid() {
		$non_existent = DatasourceCrud::get_item_by_uuid( DatasourceCrud::get_data_sources(), 'non-existent-uuid' );
		$this->assertFalse( $non_existent );
	}

	public function test_update_item_by_uuid_with_valid_uuid() {
		$source = DatasourceCrud::register_new_data_source( [
			'access_token' => 'token1',
			'service'      => 'airtable',
			'base'         => [
				'id'   => 'base_id1',
				'name' => 'Base Name 1',
			],
			'tables'       => [
				'id'   => 'table_id1',
				'name' => 'Table Name 1',
			],
			'slug'         => 'source-1',
		] );

		$updated_source = DatasourceCrud::update_item_by_uuid( $source->to_array()['uuid'], [
			'access_token' => 'updated_token',
			'slug'         => 'updated-slug',
		] );

		$this->assertIsArray( $updated_source );
		$this->assertSame( 'updated_token', $updated_source['access_token'] );
		$this->assertSame( 'updated-slug', $updated_source['slug'] );
	}

	public function test_update_item_by_uuid_with_invalid_uuid() {
		$non_existent = DatasourceCrud::update_item_by_uuid( 'non-existent-uuid', [ 'token' => 'new_token' ] );
		$this->assertInstanceOf( WP_Error::class, $non_existent );
	}

	public function test_delete_item_by_uuid() {
		$source = DatasourceCrud::register_new_data_source( [
			'access_token' => 'token1',
			'service'      => 'airtable',
			'base'         => [
				'id'   => 'base_id1',
				'name' => 'Base Name 1',
			],
			'tables'       => [
				'id'   => 'table_id1',
				'name' => 'Table Name 1',
			],
			'slug'         => 'source-1',
		] );

		$result = DatasourceCrud::delete_item_by_uuid( $source->to_array()['uuid'] );
		$this->assertTrue( $result );

		$deleted_source = DatasourceCrud::get_item_by_uuid( DatasourceCrud::get_data_sources(), $source->to_array()['uuid'] );
		$this->assertFalse( $deleted_source );
	}
}
