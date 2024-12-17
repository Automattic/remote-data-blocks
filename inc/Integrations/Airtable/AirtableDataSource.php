<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Airtable;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use WP_Error;

class AirtableDataSource extends HttpDataSource {
	protected const SERVICE_SCHEMA_VERSION = 1;

	protected const SERVICE_SCHEMA = [
		'type' => 'object',
		'properties' => [
			'service' => [
				'type' => 'string',
				'const' => REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE,
			],
			'service_schema_version' => [
				'type' => 'integer',
				'const' => self::SERVICE_SCHEMA_VERSION,
			],
			'access_token' => [ 'type' => 'string' ],
			'base' => [
				'type' => 'object',
				'properties' => [
					'id' => [ 'type' => 'string' ],
					'name' => [
						'type' => 'string',
						'required' => false,
					],
				],
			],
			'tables' => [
				'type' => 'array',
				'items' => [
					'type' => 'object',
					'properties' => [
						'id' => [ 'type' => 'string' ],
						'name' => [
							'type' => 'string',
							'required' => false,
						],
						'output_query_mappings' => [
							'type' => 'array',
							'items' => [
								'type' => 'object',
								'properties' => [
									'key' => [ 'type' => 'string' ],
									'name' => [
										'type' => 'string',
										'required' => false,
									],
									'type' => [
										'type' => 'string',
										'required' => false,
									],
									'path' => [
										'type' => 'string',
										'required' => false,
									],
									'prefix' => [
										'type' => 'string',
										'required' => false,
									],
								],
							],
						],
					],
				],
			],
			'display_name' => [
				'type' => 'string',
				'required' => true,
			],
		],
	];

	public function get_display_name(): string {
		return sprintf( 'Airtable (%s)', $this->config['display_name'] ?? $this->config['base']['name'] );
	}

	public function get_endpoint(): string {
		return 'https://api.airtable.com/v0/' . $this->config['base']['id'];
	}

	public function get_request_headers(): array|WP_Error {
		return [
			'Authorization' => sprintf( 'Bearer %s', $this->config['access_token'] ),
			'Content-Type' => 'application/json',
		];
	}

	public static function create( string $access_token, string $base_id, ?array $tables = [], ?string $display_name = null ): self {
		return parent::from_array([
			'service' => REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE,
			'access_token' => $access_token,
			'base' => [ 'id' => $base_id ],
			'tables' => $tables,
			'display_name' => $display_name,
		]);
	}

	public function to_ui_display(): array {
		return [
			'service' => REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE,
			'base' => [
				'id' => $this->config['base']['id'],
				'name' => $this->config['base']['name'] ?? null,
			],
			'tables' => $this->config['tables'] ?? [],
			'uuid' => $this->config['uuid'] ?? null,
			'display_name' => $this->config['display_name'] ?? null,
		];
	}

	public function ___temp_get_query(): AirtableGetItemQuery|\WP_Error {
		$input_schema = [
			'record_id' => [
				'name' => 'Record ID',
				'type' => 'id',
			],
		];

		$output_schema = [
			'is_collection' => false,
			'mappings' => [
				'id' => [
					'name' => 'Record ID',
					'path' => '$.id',
					'type' => 'id',
				],
			],
		];

		foreach ( $this->config['tables'][0]['output_query_mappings'] as $mapping ) {
			$mapping_key = $mapping['key'];
			$output_schema['mappings'][ $mapping_key ] = [
				'name' => $mapping['name'] ?? $mapping_key,
				'path' => $mapping['path'] ?? '$.fields["' . $mapping_key . '"]',
				'type' => $mapping['type'] ?? 'string',
			];

			if ( 'currency' === $mapping['type'] && isset( $mapping['prefix'] ) ) {
				$output_schema['mappings'][ $mapping_key ]['prefix'] = $mapping['prefix'];
			}
		}

		return AirtableGetItemQuery::from_array([
			'data_source' => $this,
			'input_schema' => $input_schema,
			'output_schema' => $output_schema,
		]);
	}

	public function ___temp_get_list_query(): AirtableListItemsQuery|\WP_Error {
		$output_schema = [
			'root_path' => '$.records[*]',
			'is_collection' => true,
			'mappings' => [
				'record_id' => [
					'name' => 'Record ID',
					'path' => '$.id',
					'type' => 'id',
				],
			],
		];

		foreach ( $this->config['tables'][0]['output_query_mappings'] as $mapping ) {
			$output_schema['mappings'][ $mapping['name'] ] = [
				'name' => $mapping['name'],
				'path' => '$.fields.' . $mapping['name'],
				'type' => $mapping['type'] ?? 'string',
			];
		}

		return AirtableListItemsQuery::from_array([
			'data_source' => $this,
			'input_schema' => [],
			'output_schema' => $output_schema,
			'query_name' => $this->config['tables'][0]['name'],
		]);
	}
}
