<?php

namespace RemoteDataBlocks\Tests\WpdbStorage;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\WpdbStorage\DatasourceCrud;
use WP_Error;

class DatasourceCrudTest extends TestCase {
	protected function tearDown(): void {
		clear_mocked_options();
	}

	public function test_validate_slug_with_valid_input() {
		$this->assertTrue( DatasourceCrud::validate_slug( 'valid-slug' ) );
	}

	public function test_validate_slug_with_invalid_input() {
		$this->assertInstanceOf( WP_Error::class, DatasourceCrud::validate_slug( '' ) );
		$this->assertInstanceOf( WP_Error::class, DatasourceCrud::validate_slug( 'INVALID_SLUG' ) );
	}

	public function test_register_new_data_source_with_valid_input() {
		$valid_source = [
			'token'   => 'valid_token',
			'service' => 'airtable',
			'base'    => [
				'id'   => 'base_id',
				'name' => 'Base Name',
			],
			'table'   => [
				'id'   => 'table_id',
				'name' => 'Table Name',
			],
			'slug'    => 'valid-slug',
		];

		$result = DatasourceCrud::register_new_data_source( $valid_source );

		$this->assertIsObject( $result );
		$this->assertTrue( wp_is_uuid( $result['uuid'] ) );
	}

	public function test_register_new_data_source_with_invalid_input() {
		$invalid_source = [
			'service' => 'unsupported',
			'slug'    => 'valid-slug',
		];

		$this->assertInstanceOf( WP_Error::class, DatasourceCrud::register_new_data_source( $invalid_source ) );
	}

	public function test_get_data_sources() {
		$source1 = DatasourceCrud::register_new_data_source( [
			'token'   => 'token1',
			'service' => 'airtable',
			'base'    => [
				'id'   => 'base_id1',
				'name' => 'Base Name 1',
			],
			'table'   => [
				'id'   => 'table_id1',
				'name' => 'Table Name 1',
			],
			'slug'    => 'source-1',
		] );

		$source2 = DatasourceCrud::register_new_data_source( [
			'token'   => 'token2',
			'service' => 'shopify',
			'store'   => 'mystore.myshopify.com',
			'slug'    => 'source-2',
		] );

		set_mocked_option( DatasourceCrud::CONFIG_OPTION_NAME, [
			$source1,
			$source2,
		] );

		$all_sources = DatasourceCrud::get_data_sources();
		$this->assertCount( 2, $all_sources );

		$airtable_sources = DatasourceCrud::get_data_sources( 'airtable' );
		$this->assertCount( 1, $airtable_sources );
		$this->assertSame( 'source-1', $airtable_sources[0]->slug );

		$shopify_sources = DatasourceCrud::get_data_sources( 'shopify' );
		$this->assertCount( 1, $shopify_sources );
		$this->assertSame( 'source-2', $shopify_sources[0]->slug );
	}

	public function test_get_item_by_uuid_with_valid_uuid() {
		$source = DatasourceCrud::register_new_data_source( [
			'token'   => 'token1',
			'service' => 'airtable',
			'base'    => [
				'id'   => 'base_id1',
				'name' => 'Base Name 1',
			],
			'table'   => [
				'id'   => 'table_id1',
				'name' => 'Table Name 1',
			],
			'slug'    => 'source-1',
		] );

		$retrieved_source = DatasourceCrud::get_item_by_uuid( DatasourceCrud::get_data_sources(), $source['uuid'] );
		$this->assertSame( $source, $retrieved_source );
	}

	public function test_get_item_by_uuid_with_invalid_uuid() {
		$non_existent = DatasourceCrud::get_item_by_uuid( DatasourceCrud::get_data_sources(), 'non-existent-uuid' );
		$this->assertFalse( $non_existent );
	}

	public function test_update_item_by_uuid_with_valid_uuid() {
		$source = DatasourceCrud::register_new_data_source( [
			'token'   => 'token1',
			'service' => 'airtable',
			'base'    => [
				'id'   => 'base_id1',
				'name' => 'Base Name 1',
			],
			'table'   => [
				'id'   => 'table_id1',
				'name' => 'Table Name 1',
			],
			'slug'    => 'source-1',
		] );

		$updated_source = DatasourceCrud::update_item_by_uuid( $source['uuid'], [
			'token' => 'updated_token',
			'slug'  => 'updated-slug',
		] );

		$this->assertIsObject( $updated_source );
		$this->assertSame( 'updated_token', $updated_source->token );
		$this->assertSame( 'updated-slug', $updated_source->slug );
	}

	public function test_update_item_by_uuid_with_invalid_uuid() {
		$non_existent = DatasourceCrud::update_item_by_uuid( 'non-existent-uuid', [ 'token' => 'new_token' ] );
		$this->assertInstanceOf( WP_Error::class, $non_existent );
	}

	public function test_delete_item_by_uuid() {
		$source = DatasourceCrud::register_new_data_source( [
			'token'   => 'token1',
			'service' => 'airtable',
			'base'    => [
				'id'   => 'base_id1',
				'name' => 'Base Name 1',
			],
			'table'   => [
				'id'   => 'table_id1',
				'name' => 'Table Name 1',
			],
			'slug'    => 'source-1',
		] );

		$result = DatasourceCrud::delete_item_by_uuid( $source['uuid'] );
		$this->assertTrue( $result );

		$deleted_source = DatasourceCrud::get_item_by_uuid( DatasourceCrud::get_data_sources(), $source['uuid'] );
		$this->assertFalse( $deleted_source );
	}
}
