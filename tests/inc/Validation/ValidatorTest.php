<?php

namespace RemoteDataBlocks\Tests\Validation;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Integrations\Airtable\AirtableDatasource;
use RemoteDataBlocks\Integrations\Shopify\ShopifyDatasource;
use RemoteDataBlocks\Validation\Validator;
use WP_Error;

class ValidatorTest extends TestCase {
	public function test_validate_airtable_source_with_valid_input() {
		$valid_source = [
			'uuid'         => '123e4567-e89b-12d3-a456-426614174000',
			'access_token' => 'valid_token',
			'service'      => 'airtable',
			'base'         => [
				'id'   => 'base_id',
				'name' => 'Base Name',
			],
			'tables'        => [
				'id'   => 'table_id',
				'name' => 'Table Name',
			],
			'slug'         => 'valid-slug',
		];

		$validator = new Validator( AirtableDatasource::get_config_schema() );
		$this->assertTrue( $validator->validate( $valid_source ) );
	}

	public function test_validate_airtable_source_with_invalid_input() {
		$invalid_source = [
			'uuid'    => '123e4567-e89b-12d3-a456-426614174000',
			'service' => 'airtable',
			'slug'    => 'valid-slug',
		];

		$validator = new Validator( AirtableDatasource::get_config_schema() );
		$this->assertInstanceOf( WP_Error::class, $validator->validate( $invalid_source ) );
	}

	public function test_validate_shopify_source_with_valid_input() {
		$valid_source = [
			'uuid'         => '123e4567-e89b-12d3-a456-426614174000',
			'access_token' => 'valid_token',
			'service'      => 'shopify',
			'store_name'   => 'mystore.myshopify.com',
			'slug'         => 'valid-slug',
		];
	
		$validator = new Validator( ShopifyDatasource::get_config_schema() );
		$this->assertTrue( $validator->validate( $valid_source ) );
	}

	public function test_validate_shopify_source_with_invalid_input() {
		$invalid_source = [
			'uuid'    => '123e4567-e89b-12d3-a456-426614174000',
			'service' => 'shopify',
			'slug'    => 'valid-slug',
		];

		$validator = new Validator( ShopifyDatasource::get_config_schema() );
		$this->assertInstanceOf( WP_Error::class, $validator->validate( $invalid_source ) );
	}
	/*
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

		$validator = new Validator( GoogleSheetsDatasource::get_config_schema() );
		$this->assertTrue($validator->validate( $valid_source ));
	}

	public function test_validate_google_sheets_source_with_invalid_input() {
		$invalid_source = (object) [
			'uuid'        => '123e4567-e89b-12d3-a456-426614174000',
			'service'     => 'google-sheets',
			'credentials' => [],
			'slug'        => 'valid-slug',
		];

		$validator = new Validator( GoogleSheetsDatasource::get_config_schema() );
		$this->assertInstanceOf( WP_Error::class, $validator->validate( $invalid_source ) );
	}
	*/
}
