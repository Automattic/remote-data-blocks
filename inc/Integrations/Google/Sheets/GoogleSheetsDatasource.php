<?php

namespace RemoteDataBlocks\Integrations\Google\Sheets;

use RemoteDataBlocks\Config\ArraySerializableInterface;
use RemoteDataBlocks\Config\Datasource\DatasourceInterface;
use RemoteDataBlocks\Config\Datasource\HttpDatasource;
use RemoteDataBlocks\Integrations\Google\Auth\GoogleAuth;

class GoogleSheetsDatasource extends HttpDatasource implements ArraySerializableInterface {
	private const SERVICE_SCHEMA = [
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

	public function get_display_name(): string {
		return sprintf( 'Google Sheets: %s - %s', $this->config['spreadsheet']['name'], $this->config['sheet']['name'] );
	}

	public function get_endpoint(): string {
		return sprintf('https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s', 
			$this->config['spreadsheet']['id'], 
			urlencode( $this->config['sheet']['name'] )
		);
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

	public static function get_config_schema(): array {
		$schema               = DatasourceInterface::BASE_SCHEMA;
		$schema['properties'] = array_merge( DatasourceInterface::BASE_SCHEMA['properties'], self::SERVICE_SCHEMA['properties'] );
		return $schema;
	}
}
