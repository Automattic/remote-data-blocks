<?php

declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Validation;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Integrations\Airtable\AirtableDatasource;
use RemoteDataBlocks\Integrations\Google\Sheets\GoogleSheetsDatasource;
use RemoteDataBlocks\Integrations\Shopify\ShopifyDatasource;
use RemoteDataBlocks\Validation\Validator;
use WP_Error;

class ValidatorTest extends TestCase {
	const AIRTABLE_SCHEMA = [
		'type'       => 'object',
		'properties' => [
			'access_token' => [ 'type' => 'string' ],
			'base'         => [
				'type'       => 'object',
				'properties' => [
					'id'   => [ 'type' => 'string' ],
					'name' => [ 'type' => 'string' ],
				],
			],
			'tables'       => [
				'type'       => 'object',
				'properties' => [
					'id'   => [ 'type' => 'string' ],
					'name' => [ 'type' => 'string' ],
				],
			],
		],
	];

	const SHOPIFY_SCHEMA = [
		'type'       => 'object',
		'properties' => [
			'access_token' => [ 'type' => 'string' ],
			'store_name'   => [ 'type' => 'string' ],
		],
	];

	const GOOGLE_SHEETS_SCHEMA = [
		'type'       => 'object',
		'properties' => [       
			'credentials' => [
				'type'       => 'object',
				'properties' => [
					'type'                        => [ 'type' => 'string' ],
					'project_id'                  => [ 'type' => 'string' ],
					'private_key_id'              => [ 'type' => 'string' ],
					'private_key'                 => [ 'type' => 'string' ],
					'client_email'                => [
						'type'     => 'string',
						'callback' => 'is_email',
						'sanitize' => 'sanitize_email',
					],
					'client_id'                   => [ 'type' => 'string' ],
					'auth_uri'                    => [
						'type'     => 'string',
						'sanitize' => 'sanitize_url',
					],
					'token_uri'                   => [
						'type'     => 'string',
						'sanitize' => 'sanitize_url',
					],
					'auth_provider_x509_cert_url' => [
						'type'     => 'string',
						'sanitize' => 'sanitize_url',
					],
					'client_x509_cert_url'        => [
						'type'     => 'string',
						'sanitize' => 'sanitize_url',
					],
					'universe_domain'             => [ 'type' => 'string' ],
				],
			],
			'spreadsheet' => [
				'type'       => 'object',
				'properties' => [
					'id'   => [ 'type' => 'string' ],
					'name' => [ 'type' => 'string' ],
				],
			],
			'sheet'       => [
				'type'       => 'object',
				'properties' => [
					'id'   => [ 'type' => 'integer' ],
					'name' => [ 'type' => 'string' ],
				],
			],
		],
	];

	public function test_validate_airtable_source_with_valid_input() {
		$valid_source = [
			'uuid'         => '123e4567-e89b-12d3-a456-426614174000',
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

		$validator = new Validator( self::AIRTABLE_SCHEMA );
		$this->assertTrue( $validator->validate( $valid_source ) );
	}

	public function test_validate_airtable_source_with_invalid_input() {
		$invalid_source = [
			'uuid'    => '123e4567-e89b-12d3-a456-426614174000',
			'service' => 'airtable',
			'slug'    => 'valid-slug',
		];

		$validator = new Validator( self::AIRTABLE_SCHEMA );
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
	
		$validator = new Validator( self::SHOPIFY_SCHEMA );
		$this->assertTrue( $validator->validate( $valid_source ) );
	}

	public function test_validate_shopify_source_with_invalid_input() {
		$invalid_source = [
			'uuid'    => '123e4567-e89b-12d3-a456-426614174000',
			'service' => 'shopify',
			'slug'    => 'valid-slug',
		];

		$validator = new Validator( self::SHOPIFY_SCHEMA );
		$this->assertInstanceOf( WP_Error::class, $validator->validate( $invalid_source ) );
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

		$valid_source = [
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

		$validator = new Validator( self::GOOGLE_SHEETS_SCHEMA );
		$this->assertTrue( $validator->validate( $valid_source ) );
	}

	public function test_validate_google_sheets_source_with_invalid_input() {
		$invalid_source = [
			'uuid'        => '123e4567-e89b-12d3-a456-426614174000',
			'service'     => 'google-sheets',
			'credentials' => [
				'type' => 'service_account',
			],
			'slug'        => 'valid-slug',
		];

		$validator = new Validator( self::GOOGLE_SHEETS_SCHEMA );
		$result    = $validator->validate( $invalid_source );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'missing_field', $result->get_error_code() );
	}

	public function test_validate_nested_array_with_valid_input() {
		$valid_nested_source = [
			'uuid'     => '123e4567-e89b-12d3-a456-426614174000',
			'service'  => 'valid-nested-service',
			'whatever' => [
				'level1'        => [
					'level2'       => [
						'key1' => 'value1',
						'key2' => 42,
					],
					'simple_array' => [ 'item1', 'item2', 'item3' ],
				],
				'boolean_field' => true,
				'enum_field'    => 'option2',
			],
			'slug'     => 'valid-nested-slug',
		];

		$schema = [
			'type'       => 'object',
			'properties' => [   
				'uuid'     => [ 'type' => 'string' ],
				'service'  => [ 'type' => 'string' ],
				'whatever' => [
					'type'       => 'object',
					'properties' => [
						'level1'        => [
							'type'       => 'object',
							'properties' => [
								'level2'       => [
									'type'       => 'object',
									'properties' => [
										'key1' => [ 'type' => 'string' ],
										'key2' => [ 'type' => 'integer' ],
									],
								],
								'simple_array' => [
									'type'  => 'array',
									'items' => [ 'type' => 'string' ],
								],
							],
						],
						'boolean_field' => [ 'type' => 'boolean' ],
						'enum_field'    => [
							'type' => 'string',
							'enum' => [ 'option1', 'option2', 'option3' ],
						],
					],
				],
				'slug'     => [ 'type' => 'string' ],
			],
		];

		$validator = new Validator( $schema );
		$this->assertTrue( $validator->validate( $valid_nested_source ) );
	}

	public function test_validate_nested_array_with_invalid_input() {
		$invalid_nested_source = [
			'uuid'     => '123e4567-e89b-12d3-a456-426614174000',
			'service'  => 'invalid-nested-service',
			'whatever' => [
				'level1'        => [
					'level2'      => [
						'key1' => 'value1',
						'key2' => 'not_an_integer', // This should be an integer
					],
					'array_field' => 'not_an_array', // This should be an array
				],
				'boolean_field' => 'not_a_boolean', // This should be a boolean
			],
			'slug'     => 'valid-nested-slug',
		];

		$schema = [
			'type'       => 'object',
			'properties' => [
				'uuid'     => [ 'type' => 'string' ],
				'service'  => [ 'type' => 'string' ],
				'whatever' => [
					'type'       => 'object',
					'properties' => [
						'level1'        => [
							'type'       => 'object',
							'properties' => [
								'level2'       => [
									'type'       => 'object',
									'properties' => [
										'key1' => [ 'type' => 'string' ],
										'key2' => [ 'type' => 'integer' ],
									],
								],
								'simple_array' => [
									'type'  => 'array',
									'items' => [ 'type' => 'string' ],
								],
							],
						],
						'boolean_field' => [ 'type' => 'boolean' ],
					],
				],
				'slug'     => [ 'type' => 'string' ],
			],
		];

		$validator = new Validator( $schema );
		$result    = $validator->validate( $invalid_nested_source );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_type', $result->get_error_code() );
		$this->assertSame( 'Expected integer, got string.', $result->get_error_message() );
	}
}
