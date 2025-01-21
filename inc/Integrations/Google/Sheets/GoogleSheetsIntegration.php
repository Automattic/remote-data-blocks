<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Google\Sheets;

use RemoteDataBlocks\WpdbStorage\DataSourceCrud;
use RemoteDataBlocks\Config\Query\HttpQuery;
use WP_Error;

class GoogleSheetsIntegration {
	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_blocks' ], 10, 0 );
	}

	public static function register_blocks(): void {
		$data_source_configs = DataSourceCrud::get_configs_by_service(
			REMOTE_DATA_BLOCKS_GOOGLE_SHEETS_SERVICE
		);

		foreach ( $data_source_configs as $config ) {
			$data_source = GoogleSheetsDataSource::from_array( $config );
			self::register_blocks_for_google_sheets_data_source( $data_source );
			self::register_loop_blocks_for_google_sheets_data_source( $data_source );
		}
	}

	public static function register_blocks_for_google_sheets_data_source(
		GoogleSheetsDataSource $data_source,
		array $block_overrides = []
	): void {
		$sheets = $data_source->to_array()['service_config']['sheets'];

		foreach ( $sheets as $sheet ) {
			$query = self::get_query( $data_source, $sheet );
			$list_query = self::get_list_query( $data_source, $sheet );

			register_remote_data_block(
				array_merge(
					[
						'title' => $data_source->get_display_name() . '/' . $sheet['name'],
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

	public static function register_loop_blocks_for_google_sheets_data_source(
		GoogleSheetsDataSource $data_source,
		array $block_overrides = []
	): void {
		$sheets = $data_source->to_array()['service_config']['sheets'];

		foreach ( $sheets as $sheet ) {
			$list_query = self::get_list_query( $data_source, $sheet );

			register_remote_data_block(
				array_merge(
					[
						'title' => sprintf( '%s/%s Loop', $data_source->get_display_name(), $sheet['name'] ),
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

	private static function get_query(
		GoogleSheetsDataSource $data_source,
		array $sheet,
	): HttpQuery|WP_Error {
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

		foreach ( $sheet['output_query_mappings'] as $mapping ) {
			$mapping_key = $mapping['key'];
			$output_schema['type'][ $mapping_key ] = [
				'name' => $mapping['name'] ?? $mapping_key,
				'path' => $mapping['path'] ?? '$.fields["' . $mapping_key . '"]',
				'type' => $mapping['type'] ?? 'string',
			];
		}

		return HttpQuery::from_array( [
			'data_source' => $data_source,
			'endpoint' => $data_source->get_endpoint() . '/values/' . rawurlencode( $sheet['name'] ),
			'input_schema' => $input_schema,
			'output_schema' => $output_schema,
			'preprocess_response' => function ( mixed $response_data, array $input_variables ): array {
				return GoogleSheetsDataSource::preprocess_get_response( $response_data, $input_variables );
			},
		] );
	}

	private static function get_list_query(
		GoogleSheetsDataSource $data_source,
		array $sheet,
	): HttpQuery|WP_Error {
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

		foreach ( $sheet['output_query_mappings'] as $mapping ) {
			$mapping_key = $mapping['key'];
			$output_schema['type'][ $mapping_key ] = [
				'name' => $mapping['name'] ?? $mapping_key,
				'path' => $mapping['path'] ?? '$.fields["' . $mapping_key . '"]',
				'type' => $mapping['type'] ?? 'string',
			];
		}

		return HttpQuery::from_array( [
			'data_source' => $data_source,
			'endpoint' => $data_source->get_endpoint() . '/values/' . rawurlencode( $sheet['name'] ),
			'input_schema' => [],
			'output_schema' => $output_schema,
			'preprocess_response' => function ( mixed $response_data ): array {
				return GoogleSheetsDataSource::preprocess_list_response( $response_data );
			},
		] );
	}
}
