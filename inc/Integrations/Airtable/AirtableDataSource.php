<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Airtable;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Validation\Types;
use WP_Error;

class AirtableDataSource extends HttpDataSource {
	protected const SERVICE_NAME = REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE;
	protected const SERVICE_SCHEMA_VERSION = 1;

	protected static function get_service_config_schema(): array {
		return Types::object( [
			'__version' => Types::integer(),
			'access_token' => Types::string(),
			'base' => Types::object( [
				'id' => Types::string(),
				'name' => Types::nullable( Types::string() ),
			] ),
			'display_name' => Types::string(),
			'tables' => Types::list_of(
				Types::object( [
					'id' => Types::id(),
					'name' => Types::nullable( Types::string() ),
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
			'endpoint' => sprintf( 'https://api.airtable.com/v0/%s', $service_config['base']['id'] ),
			'request_headers' => [
				'Authorization' => sprintf( 'Bearer %s', $service_config['access_token'] ),
				'Content-Type' => 'application/json',
			],
		];
	}

	public function ___temp_get_query(): HttpQuery|WP_Error {
		$input_schema = [
			'record_id' => [
				'name' => 'Record ID',
				'type' => 'id',
			],
		];

		$output_schema = [
			'is_collection' => false,
			'type' => [
				'id' => [
					'name' => 'Record ID',
					'path' => '$.id',
					'type' => 'id',
				],
			],
		];

		foreach ( $this->config['service_config']['tables'][0]['output_query_mappings'] as $mapping ) {
			$mapping_key = $mapping['key'];
			$output_schema['type'][ $mapping_key ] = [
				'name' => $mapping['name'] ?? $mapping_key,
				'path' => $mapping['path'] ?? '$.fields["' . $mapping_key . '"]',
				'type' => $mapping['type'] ?? 'string',
			];
		}

		return HttpQuery::from_array( [
			'data_source' => $this,
			'endpoint' => function ( array $input_variables ): string {
				return $this->get_endpoint() . '/' . $this->config['service_config']['tables'][0]['id'] . '/' . $input_variables['record_id'];
			},
			'input_schema' => $input_schema,
			'output_schema' => $output_schema,
		] );
	}

	public function ___temp_get_list_query(): HttpQuery|WP_Error {
		$output_schema = [
			'is_collection' => true,
			'path' => '$.records[*]',
			'type' => [
				'record_id' => [
					'name' => 'Record ID',
					'path' => '$.id',
					'type' => 'id',
				],
			],
		];

		foreach ( $this->config['service_config']['tables'][0]['output_query_mappings'] as $mapping ) {
			$output_schema['type'][ $mapping['name'] ] = [
				'name' => $mapping['name'],
				'path' => '$.fields.' . $mapping['name'],
				'type' => $mapping['type'] ?? 'string',
			];
		}

		return HttpQuery::from_array( [
			'data_source' => $this,
			'endpoint' => $this->get_endpoint() . '/' . $this->config['service_config']['tables'][0]['id'],
			'input_schema' => [],
			'output_schema' => $output_schema,
		] );
	}
}
