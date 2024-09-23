<?php

namespace RemoteDataBlocks\Integrations\Google\Sheets;

<<<<<<< HEAD
use RemoteDataBlocks\Config\ArraySerializableInterface;
=======
>>>>>>> trunk
use RemoteDataBlocks\Config\Datasource\DatasourceInterface;
use RemoteDataBlocks\Config\Datasource\HttpDatasource;
use RemoteDataBlocks\Integrations\Google\Auth\GoogleAuth;

<<<<<<< HEAD
class GoogleSheetsDatasource extends HttpDatasource implements ArraySerializableInterface {
	private const SERVICE_SCHEMA = [
		'type'       => 'object',
		'properties' => [       
			'credentials' => [
=======
class GoogleSheetsDatasource extends HttpDatasource {
	private array $credentials;
	private string $endpoint;
	private string $display_name;

	private const SERVICE_SCHEMA = [
		'type'       => 'object',
		'properties' => [       
			'credentials'    => [
>>>>>>> trunk
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
			'spreadsheet_id' => [ 'type' => 'string' ],
		],
	];

	public function __construct( string $credentials, string $endpoint, string $display_name ) {
		/**
		 * Decodes Base64 encoded JSON string into an array
		 * and assigns it to the $credentials property.
		 */
		$this->credentials  = json_decode( base64_decode( $credentials ), true );
		$this->endpoint     = $endpoint;
		$this->display_name = $display_name;
	}

	public function get_display_name(): string {
		return sprintf( 'Google Sheets: %s', $this->display_name );
	}

	public function get_endpoint(): string {
		return $this->endpoint;
	}

	public function get_request_headers(): array {
		$access_token = GoogleAuth::generate_token_from_service_account_key(
			$this->credentials,
			GoogleAuth::GOOGLE_SHEETS_SCOPES
		);

		return [
			'Authorization' => sprintf( 'Bearer %s', $access_token ),
			'Content-Type'  => 'application/json',
		];
	}
}
