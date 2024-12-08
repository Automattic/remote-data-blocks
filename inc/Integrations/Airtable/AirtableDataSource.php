<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Airtable;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Validation\Types;
use RemoteDataBlocks\Validation\Validator;
use WP_Error;

class AirtableDataSource extends HttpDataSource {
	protected const SERVICE_NAME = REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE;
	protected const SERVICE_SCHEMA_VERSION = 1;

	public static function create( array $service_config, array $config_overrides = [] ): self|WP_Error {
		$validator = new Validator( self::get_service_config_schema() );
		$validated = $validator->validate( $service_config );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$display_id = $service_config['display_name'] ?? $service_config['base']['name'] ?? $service_config['base']['id'];
		$display_name = sprintf( 'Airtable (%s)', $display_id );

		return self::from_array(
			array_merge(
				[
					'display_name' => sprintf( 'GitHub: %s/%s (%s)', $service_config['repo_owner'], $service_config['repo_name'], $service_config['ref'] ),
					'endpoint' => sprintf( 'https://api.airtable.com/v0/%s', $service_config['base']['id'] ),
					'request_headers' => [
						'Authorization' => sprintf( 'Bearer %s', $service_config['access_token'] ),
						'Content-Type' => 'application/json',
					],
					'service' => REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE,
					'service_config' => $service_config,
					'slug' => sanitize_title( $display_name ),
				],
				$config_overrides
			)
		);
	}

	private static function get_service_config_schema(): array {
		return Types::object( [
			'access_token' => Types::string(),
			'base' => Types::object( [
				'id' => Types::string(),
				'name' => Types::nullable( Types::string() ),
			] ),
			'display_name' => Types::nullable( Types::string() ),
			'tables' => Types::list_of(
				Types::object( [
					'id' => Types::id(),
					'name' => Types::nullable( Types::string() ),
					'output_query_mappings' => Types::list_of(
						Types::object( [
							'name' => Types::string(),
							'type' => Types::nullable( Types::string() ),
						] )
					),
				] )
			),
		] );
	}

	public function to_ui_display(): array {
		return array_merge(
			parent::to_ui_display(),
			[
				'base' => [
					'id' => $this->config['base']['id'],
					'name' => $this->config['base']['name'] ?? null,
				],
				'tables' => $this->config['tables'] ?? [],
				'uuid' => $this->config['uuid'] ?? null,
			]
		);
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
			'type' => [
				'id' => [
					'name' => 'Record ID',
					'path' => '$.id',
					'type' => 'id',
				],
			],
		];

		foreach ( $this->config['service_config']['tables'][0]['output_query_mappings'] as $mapping ) {
			$output_schema['type'][ ucfirst( $mapping['name'] ) ] = [
				'name' => $mapping['name'],
				'path' => '$.fields.' . $mapping['name'],
				'type' => $mapping['type'] ?? 'string',
			];
		}

		return HttpQueryContext::from_array( [
			'data_source' => $this,
			'endpoint' => function ( array $input_variables ): string {
				return $this->get_endpoint() . '/' . $this->config['service_config']['tables'][0]['id'] . '/' . $input_variables['record_id'];
			},
			'input_schema' => $input_schema,
			'output_schema' => $output_schema,
			'query_name' => 'Get item',
		] );
	}

	public function ___temp_get_list_query(): AirtableListItemsQuery|\WP_Error {
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

		return HttpQueryContext::from_array( [
			'data_source' => $this,
			'endpoint' => $this->get_endpoint() . '/' . $this->config['service_config']['tables'][0]['id'],
			'input_schema' => [],
			'output_schema' => $output_schema,
			'query_name' => $this->config['service_config']['tables'][0]['name'] ?? 'List items',
		] );
	}
}
