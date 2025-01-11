<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Airtable;

use RemoteDataBlocks\WpdbStorage\DataSourceCrud;
use RemoteDataBlocks\Config\Query\HttpQuery;
use WP_Error;

class AirtableIntegration {
	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_blocks' ], 10, 0 );
	}

	public static function register_blocks(): void {
		$data_source_configs = DataSourceCrud::get_configs_by_service(
			REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE
		);

		foreach ( $data_source_configs as $config ) {
			$data_source = AirtableDataSource::from_array( $config );
			self::register_blocks_for_airtable_data_source( $data_source );
			self::register_loop_blocks_for_airtable_data_source( $data_source );
		}
	}

	public static function register_blocks_for_airtable_data_source(
		AirtableDataSource $data_source,
		array $block_overrides = []
	): void {
		$tables = $data_source->to_array()['service_config']['tables'];

		foreach ( $tables as $table ) {
			$query = self::get_query( $data_source, $table );
			$list_query = self::get_list_query( $data_source, $table );

			register_remote_data_block(
				array_merge(
					[
						'title' => $data_source->get_display_name() . '/' . $table['name'],
						'render_query' => [
							'query' => $query,
						],
						'selection_queries' => [
							[
								'query' => $list_query,
								'type' => 'list',
							],
						],
					],
					$block_overrides
				)
			);
		}
	}

	public static function register_loop_blocks_for_airtable_data_source(
		AirtableDataSource $data_source,
		array $block_overrides = []
	): void {
		$tables = $data_source->to_array()['service_config']['tables'];

		foreach ( $tables as $table ) {
			$list_query = self::get_list_query( $data_source, $table );

			register_remote_data_block(
				array_merge(
					[
						'title' => sprintf( '%s/%s Loop', $data_source->get_display_name(), $table['name'] ),
						'render_query' => [
							'loop' => true,
							'query' => $list_query,
						],
					],
					$block_overrides
				)
			);
		}
	}

	private static function get_query( AirtableDataSource $data_source, array $table ): HttpQuery|WP_Error {
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

		foreach ( $table['output_query_mappings'] as $mapping ) {
			$mapping_key = $mapping['key'];
			$output_schema['type'][ $mapping_key ] = [
				'name' => $mapping['name'] ?? $mapping_key,
				'path' => $mapping['path'] ?? '$.fields["' . $mapping_key . '"]',
				'type' => $mapping['type'] ?? 'string',
			];
		}

		return HttpQuery::from_array( [
			'data_source' => $data_source,
			'endpoint' => function ( array $input_variables ) use ( $data_source, $table ): string {
				return $data_source->get_endpoint() . '/' . $table['id'] . '/' . $input_variables['record_id'];
			},
			'input_schema' => $input_schema,
			'output_schema' => $output_schema,
		] );
	}

	private static function get_list_query( AirtableDataSource $data_source, array $table ): HttpQuery|WP_Error {
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

		foreach ( $table['output_query_mappings'] as $mapping ) {
			$mapping_key = $mapping['key'];
			$output_schema['type'][ $mapping_key ] = [
				'name' => $mapping['name'] ?? $mapping_key,
				'path' => $mapping['path'] ?? '$.fields["' . $mapping_key . '"]',
				'type' => $mapping['type'] ?? 'string',
			];
		}

		return HttpQuery::from_array( [
			'data_source' => $data_source,
			'endpoint' => $data_source->get_endpoint() . '/' . $table['id'],
			'input_schema' => [],
			'output_schema' => $output_schema,
		] );
	}
}
