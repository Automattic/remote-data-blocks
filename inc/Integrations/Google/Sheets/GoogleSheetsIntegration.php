<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Google\Sheets;

use RemoteDataBlocks\WpdbStorage\DataSourceCrud;

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
			self::register_block_for_google_sheets_data_source( $data_source );
			self::register_loop_block_for_google_sheets_data_source( $data_source );
		}
	}

	public static function register_block_for_google_sheets_data_source(
		GoogleSheetsDataSource $data_source,
		array $block_overrides = []
	): void {
		register_remote_data_block(
			array_merge(
				[
					'title' => $data_source->get_display_name(),
					'render_query' => [
						'query' => $data_source->___temp_get_query(),
					],
					'selection_queries' => [
						[
							'query' => $data_source->___temp_get_list_query(),
							'type' => 'list',
						],
					],
				],
				$block_overrides
			)
		);
	}

	public static function register_loop_block_for_google_sheets_data_source(
		GoogleSheetsDataSource $data_source,
		array $block_overrides = []
	): void {
		register_remote_data_block(
			array_merge(
				[
					'title' => sprintf( '%s Loop', $data_source->get_display_name() ),
					'render_query' => [
						'loop' => true,
						'query' => $data_source->___temp_get_list_query(),
					],
				],
				$block_overrides
			)
		);
	}
}
