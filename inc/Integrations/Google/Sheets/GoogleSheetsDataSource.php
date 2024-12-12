<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Google\Sheets;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Integrations\Google\Auth\GoogleAuth;
use RemoteDataBlocks\Validation\Types;

class GoogleSheetsDataSource extends HttpDataSource {
	protected const SERVICE_NAME = REMOTE_DATA_BLOCKS_GOOGLE_SHEETS_SERVICE;
	protected const SERVICE_SCHEMA_VERSION = 1;

	protected static function get_service_config_schema(): array {
		return Types::object( [
			'__version' => Types::integer(),
			'credentials' => Types::object( [
				'type' => Types::string(),
				'project_id' => Types::string(),
				'private_key_id' => Types::string(),
				'private_key' => Types::skip_sanitize( Types::string() ),
				'client_email' => Types::email_address(),
				'client_id' => Types::string(),
				'auth_uri' => Types::url(),
				'token_uri' => Types::url(),
				'auth_provider_x509_cert_url' => Types::url(),
				'client_x509_cert_url' => Types::url(),
				'universe_domain' => Types::string(),
			] ),
			'display_name' => Types::string(),
			'spreadsheet' => Types::object( [
				'id' => Types::id(),
				'name' => Types::nullable( Types::string() ),
			] ),
			'sheet' => Types::object( [
				'id' => Types::integer(),
				'name' => Types::string(),
			] ),
		] );
	}

	protected static function map_service_config( array $service_config ): array {
		return [
			'endpoint' => sprintf( 'https://sheets.googleapis.com/v4/spreadsheets/%s', $service_config['spreadsheet']['id'] ),
			'request_headers' => function () use ( $service_config ): array {
				$access_token = GoogleAuth::generate_token_from_service_account_key(
					$service_config['credentials'],
					GoogleAuth::GOOGLE_SHEETS_SCOPES
				);

				return [
					'Authorization' => sprintf( 'Bearer %s', $access_token ),
					'Content-Type' => 'application/json',
				];
			},
		];
	}
}
