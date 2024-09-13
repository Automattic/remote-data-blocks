<?php

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\REST\DatasourceCRUD;
use RemoteDataBlocks\Integrations\Google\Auth\GoogleServiceAccountKey;

class DatasourceCrudTest extends TestCase {
	protected function tearDown(): void {
		clear_mocked_options();
	}

	public function test_validate_slug_with_valid_input() {
		$this->assertTrue( DatasourceCRUD::validate_slug( 'valid-slug' ) );
	}

	public function test_validate_slug_with_invalid_input() {
		$this->assertInstanceOf( WP_Error::class, DatasourceCRUD::validate_slug( '' ) );
		$this->assertInstanceOf( WP_Error::class, DatasourceCRUD::validate_slug( 'INVALID_SLUG' ) );
	}

	public function test_validate_airtable_source_with_valid_input() {
		$valid_source = (object) [
			'uuid'    => '123e4567-e89b-12d3-a456-426614174000',
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

		$result = DatasourceCRUD::validate_airtable_source( $valid_source );
		$this->assertIsObject( $result );
		$this->assertEquals( $valid_source->uuid, $result->uuid );
	}

	public function test_validate_airtable_source_with_invalid_input() {
		$invalid_source = (object) [
			'uuid'    => '123e4567-e89b-12d3-a456-426614174000',
			'service' => 'airtable',
			'slug'    => 'valid-slug',
		];

		$this->assertInstanceOf( WP_Error::class, DatasourceCRUD::validate_airtable_source( $invalid_source ) );
	}

	public function test_validate_shopify_source_with_valid_input() {
		$valid_source = (object) [
			'uuid'    => '123e4567-e89b-12d3-a456-426614174000',
			'token'   => 'valid_token',
			'service' => 'shopify',
			'store'   => 'mystore.myshopify.com',
			'slug'    => 'valid-slug',
		];

		$result = DatasourceCRUD::validate_shopify_source( $valid_source );
		$this->assertIsObject( $result );
		$this->assertEquals( $valid_source->uuid, $result->uuid );
	}

	public function test_validate_shopify_source_with_invalid_input() {
		$invalid_source = (object) [
			'uuid'    => '123e4567-e89b-12d3-a456-426614174000',
			'service' => 'shopify',
			'slug'    => 'valid-slug',
		];

		$this->assertInstanceOf( WP_Error::class, DatasourceCRUD::validate_shopify_source( $invalid_source ) );
	}

	public function test_validate_google_sheets_source_with_valid_input() {
		$valid_credentials = [
			'type'                        => 'service_account',
			'project_id'                  => 'test-project',
			'private_key_id'              => '1234567890abcdef',
			'private_key'                 => '-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQC7/jHh2Wo0zkA5\n-----END PRIVATE KEY-----\n',
			'client_email'                => 'test@test-project.iam.gserviceaccount.com',
			'client_id'                   => '123456789012345678901',
			'auth_uri'                    => 'https://accounts.google.com/o/oauth2/auth',
			'token_uri'                   => 'https://oauth2.googleapis.com/token',
			'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
			'client_x509_cert_url'        => 'https://www.googleapis.com/robot/v1/metadata/x509/test%40test-project.iam.gserviceaccount.com',
			'universe_domain'             => 'googleapis.com',
		];

		$valid_source = (object) [
			'uuid'        => '123e4567-e89b-12d3-a456-426614174000',
			'service'     => 'google-sheets',
			'credentials' => $valid_credentials,
			'spreadsheet' => [
				'id'   => 'spreadsheet_id',
				'name' => 'Spreadsheet Name',
			],
			'sheet'       => [
				'id'   => 0,
				'name' => 'Sheet Name',
			],
			'slug'        => 'valid-slug',
		];

		$result = DatasourceCRUD::validate_google_sheets_source( $valid_source );
		$this->assertIsObject( $result );
		$this->assertEquals( $valid_source->uuid, $result->uuid );
	}

	public function test_validate_google_sheets_source_with_invalid_input() {
		$invalid_source = (object) [
			'uuid'        => '123e4567-e89b-12d3-a456-426614174000',
			'service'     => 'google-sheets',
			'credentials' => [],
			'slug'        => 'valid-slug',
		];

		$this->assertInstanceOf( WP_Error::class, DatasourceCRUD::validate_google_sheets_source( $invalid_source ) );
	}

	public function test_validate_source_with_valid_input() {
		$valid_source = (object) [
			'uuid'    => '123e4567-e89b-12d3-a456-426614174000',
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

		$result = DatasourceCRUD::validate_source( $valid_source );
		$this->assertIsObject( $result );
		$this->assertObjectHasProperty( 'uuid', $result );
		$this->assertEquals( $valid_source->uuid, $result->uuid );
	}

	public function test_validate_source_with_invalid_input() {
		$invalid_source = (object) [
			'uuid'    => 'invalid-uuid',
			'service' => 'unsupported',
			'slug'    => 'valid-slug',
		];

		$result = DatasourceCRUD::validate_source( $invalid_source );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'invalid_uuid', $result->get_error_code() );
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

		$result = DatasourceCRUD::register_new_data_source( $valid_source );
		$this->assertIsObject( $result );
		$this->assertTrue( wp_is_uuid( $result->uuid ) );
	}

	public function test_register_new_data_source_with_invalid_input() {
		$invalid_source = [
			'service' => 'unsupported',
			'slug'    => 'valid-slug',
		];

		$this->assertInstanceOf( WP_Error::class, DatasourceCRUD::register_new_data_source( $invalid_source ) );
	}

	public function test_get_data_sources() {
		$source1 = DatasourceCRUD::register_new_data_source( [
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

		$source2 = DatasourceCRUD::register_new_data_source( [
			'token'   => 'token2',
			'service' => 'shopify',
			'store'   => 'mystore.myshopify.com',
			'slug'    => 'source-2',
		] );

		set_mocked_option( DatasourceCRUD::CONFIG_OPTION_NAME, [
			$source1,
			$source2,
		] );

		$all_sources = DatasourceCRUD::get_data_sources();
		$this->assertCount( 2, $all_sources );

		$airtable_sources = DatasourceCRUD::get_data_sources( 'airtable' );
		$this->assertCount( 1, $airtable_sources );
		$this->assertEquals( 'source-1', $airtable_sources[0]->slug );

		$shopify_sources = DatasourceCRUD::get_data_sources( 'shopify' );
		$this->assertCount( 1, $shopify_sources );
		$this->assertEquals( 'source-2', $shopify_sources[0]->slug );
	}

	public function test_get_item_by_uuid_with_valid_uuid() {
		$source = DatasourceCRUD::register_new_data_source( [
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

		$retrieved_source = DatasourceCRUD::get_item_by_uuid( DatasourceCRUD::get_data_sources(), $source->uuid );
		$this->assertEquals( $source, $retrieved_source );
	}

	public function test_get_item_by_uuid_with_invalid_uuid() {
		$non_existent = DatasourceCRUD::get_item_by_uuid( DatasourceCRUD::get_data_sources(), 'non-existent-uuid' );
		$this->assertFalse( $non_existent );
	}

	public function test_update_item_by_uuid_with_valid_uuid() {
		$source = DatasourceCRUD::register_new_data_source( [
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

		$updated_source = DatasourceCRUD::update_item_by_uuid( $source->uuid, [
			'token' => 'updated_token',
			'slug'  => 'updated-slug',
		] );

		$this->assertIsObject( $updated_source );
		$this->assertEquals( 'updated_token', $updated_source->token );
		$this->assertEquals( 'updated-slug', $updated_source->slug );
	}

	public function test_update_item_by_uuid_with_invalid_uuid() {
		$non_existent = DatasourceCRUD::update_item_by_uuid( 'non-existent-uuid', [ 'token' => 'new_token' ] );
		$this->assertInstanceOf( WP_Error::class, $non_existent );
	}

	public function test_delete_item_by_uuid() {
		$source = DatasourceCRUD::register_new_data_source( [
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

		$result = DatasourceCRUD::delete_item_by_uuid( $source->uuid );
		$this->assertTrue( $result );

		$deleted_source = DatasourceCRUD::get_item_by_uuid( DatasourceCRUD::get_data_sources(), $source->uuid );
		$this->assertFalse( $deleted_source );
	}
}
