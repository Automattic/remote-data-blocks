<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Google\Sheets;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Integrations\Google\Auth\GoogleAuth;
use RemoteDataBlocks\Validation\Types;
use RemoteDataBlocks\Validation\Validator;
use WP_Error;

class GoogleSheetsDataSource extends HttpDataSource {
	protected const SERVICE_NAME = REMOTE_DATA_BLOCKS_GOOGLE_SHEETS_SERVICE;
	protected const SERVICE_SCHEMA_VERSION = 1;

	public static function create( array $service_config, array $config_overrides = [] ): self|WP_Error {
		$validator = new Validator( self::get_service_config_schema() );
		$validated = $validator->validate( $service_config );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$display_name = sprintf( 'Google Sheets: %s', $service_config['display_name'] );

		return self::from_array(
			array_merge(
				[
					'display_name' => $display_name,
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
					'service' => REMOTE_DATA_BLOCKS_GITHUB_SERVICE,
					'service_config' => $service_config,
					'slug' => sanitize_title( $display_name ),
				],
				$config_overrides
			)
		);
	}

	private static function get_service_config_schema(): array {
		return Types::object( [
			'credentials' => Types::object( [
				'type' => Types::string(),
				'project_id' => Types::string(),
				'private_key_id' => Types::string(),
				'private_key' => Types::string( /* TODO sanitize: false? */ ),
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
				'name' => Types::string(),
				'id' => Types::integer(),
			] ),
		] );
	}

	public function to_ui_display(): array {
		return array_merge(
			parent::to_ui_display(),
			[
				'sheet' => [ 'name' => '' ],
				'spreadsheet' => [ 'name' => $this->config['spreadsheet_id'] ],
				'uuid' => $this->config['uuid'] ?? null,
			]
		);
	}
}
