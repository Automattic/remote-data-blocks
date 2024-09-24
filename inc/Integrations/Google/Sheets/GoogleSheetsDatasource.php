<?php

namespace RemoteDataBlocks\Integrations\Google\Sheets;

use RemoteDataBlocks\Config\ArraySerializableInterface;
use RemoteDataBlocks\Config\Datasource\HttpDatasource;
use RemoteDataBlocks\Integrations\Google\Auth\GoogleAuth;
use WP_Error;

class GoogleSheetsDatasource extends HttpDatasource implements ArraySerializableInterface {
	protected const SERVICE_NAME           = REMOTE_DATA_BLOCKS_GOOGLE_SHEETS_SERVICE;
	protected const SERVICE_SCHEMA_VERSION = 1;

	protected const SERVICE_SCHEMA = [
		'type'       => 'object',
		'properties' => [       
			'credentials'    => [
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
			'display_name'   => [ 'type' => 'string' ],
			'spreadsheet_id' => [ 'type' => 'string' ],
		],
	];

	public function get_display_name(): string {
		return sprintf( 'Google Sheets: %s', $this->config['display_name'] );
	}

	public function get_endpoint(): string {
		return sprintf( 'https://sheets.googleapis.com/v4/spreadsheets/%s', $this->config['spreadsheet_id'] );
	}

	public function get_request_headers(): array {
		$access_token = GoogleAuth::generate_token_from_service_account_key(
			$this->config['credentials'],
			GoogleAuth::GOOGLE_SHEETS_SCOPES
		);

		return [
			'Authorization' => sprintf( 'Bearer %s', $access_token ),
			'Content-Type'  => 'application/json',
		];
	}

	public static function create( array $credentials, string $spreadsheet_id, string $display_name ): self|WP_Error {
		return parent::from_array([
			'service'        => REMOTE_DATA_BLOCKS_GOOGLE_SHEETS_SERVICE,
			'credentials'    => $credentials,
			'display_name'   => $display_name,
			'spreadsheet_id' => $spreadsheet_id,
			'slug'           => sanitize_title( $display_name ),
		]);
	}

	public function to_ui_display(): array {
		return [
			'slug'        => $this->get_slug(),
			'service'     => REMOTE_DATA_BLOCKS_GOOGLE_SHEETS_SERVICE,
			'spreadsheet' => [ 'name' => $this->config['spreadsheet_id'] ],
			'sheet'       => [ 'name' => '' ],
			'uuid'        => $this->config['uuid'],
		];
	}
}
