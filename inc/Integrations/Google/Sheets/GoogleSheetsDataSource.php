<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Google\Sheets;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;
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
			'sheets' => Types::list_of(
				Types::object( [
					'id' => Types::string(),
					'name' => Types::string(),
					'output_query_mappings' => Types::list_of(
						Types::object( [
							'key' => Types::string(),
							'name' => Types::nullable( Types::string() ),
							'path' => Types::nullable( Types::json_path() ),
							'type' => Types::nullable( Types::string() ),
						] )
					),
				] )
			),
		] );
	}

	protected static function map_service_config( array $service_config ): array {
		return [
			'display_name' => $service_config['display_name'],
			'endpoint' => sprintf(
				'https://sheets.googleapis.com/v4/spreadsheets/%s',
				$service_config['spreadsheet']['id']
			),
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

	public function ___temp_get_query(): HttpQuery {
		$service_config = $this->config['service_config'];

		$input_schema = [
			'row_id' => [
				'name' => 'Row ID',
				'type' => 'id',
			],
		];

		$output_schema = [
			'is_collection' => false,
			'type' => [
				'row_id' => [
					'name' => 'Row ID',
					'path' => '$.RowId',
					'type' => 'id',
				],
			],
		];

		foreach ( $service_config['sheets'][0]['output_query_mappings'] as $mapping ) {
			$mapping_key = $mapping['key'];
			$output_schema['type'][ $mapping_key ] = [
				'name' => $mapping['name'] ?? $mapping_key,
				'path' => $mapping['path'] ?? '$.fields["' . $mapping_key . '"]',
				'type' => $mapping['type'] ?? 'string',
			];
		}

		return HttpQuery::from_array( [
			'data_source' => $this,
			'endpoint' => function (): string {
				return $this->get_endpoint() . '/values/' . $this->config['service_config']['sheets'][0]['name'];
			},
			'input_schema' => $input_schema,
			'output_schema' => $output_schema,
			'preprocess_response' => function ( mixed $response_data, array $input_variables ): array {
				return GoogleSheetsDataSource::preprocess_get_response( $response_data, $input_variables );
			},
		] );
	}

	public function ___temp_get_list_query(): HttpQuery {
		$service_config = $this->config['service_config'];

		$output_schema = [
			'is_collection' => true,
			'path' => '$.values[*]',
			'type' => [
				'row_id' => [
					'name' => 'Row ID',
					'path' => '$.RowId',
					'type' => 'id',
				],
			],
		];

		foreach ( $service_config['sheets'][0]['output_query_mappings'] as $mapping ) {
			$mapping_key = $mapping['key'];
			$output_schema['type'][ $mapping_key ] = [
				'name' => $mapping['name'] ?? $mapping_key,
				'path' => $mapping['path'] ?? '$.fields["' . $mapping_key . '"]',
				'type' => $mapping['type'] ?? 'string',
			];
		}

		return HttpQuery::from_array( [
			'data_source' => $this,
			'endpoint' => function (): string {
				return $this->get_endpoint() . '/values/' . $this->config['service_config']['sheets'][0]['name'];
			},
			'input_schema' => [],
			'output_schema' => $output_schema,
			'preprocess_response' => function ( mixed $response_data ): array {
				return GoogleSheetsDataSource::preprocess_list_response( $response_data );
			},
		] );
	}

	public static function preprocess_list_response( array $response_data ): array {
		if ( isset( $response_data['values'] ) && is_array( $response_data['values'] ) ) {
			$values = $response_data['values'];
			$columns = array_shift( $values ); // Get column names from first row

			$response_data['values'] = array_map(
				function ( $row, $index ) use ( $columns ) {
					$combined = array_combine( $columns, $row );
					$combined['RowId'] = $index + 1; // Add row_id field, starting from 1
					return $combined;
				},
				$values,
				array_keys( $values )
			);
		}

		return $response_data;
	}

	public static function preprocess_get_response( array $response_data, array $input_variables ): array {
		$selected_row = null;
		$row_id = $input_variables['row_id'];
		
		if ( isset( $response_data['values'] ) && is_array( $response_data['values'] ) ) {
			$values = $response_data['values'];
			$columns = array_shift( $values ); // Get column names from first row
			$raw_selected_row = $values[ $row_id - 1 ];
			if ( is_array( $raw_selected_row ) ) {
				$selected_row = array_combine( $columns, $raw_selected_row );
				$selected_row['RowId'] = $row_id;
			}
		}

		return $selected_row;
	}
}
