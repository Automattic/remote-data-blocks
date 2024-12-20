<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Airtable;

use RemoteDataBlocks\WpdbStorage\DataSourceCrud;

class AirtableIntegration {
	public static function init(): void {
		$data_source_configs = DataSourceCrud::get_configs_by_service( REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE );

		foreach ( $data_source_configs as $config ) {
			$data_source = AirtableDataSource::from_array( $config );
			self::register_block_for_airtable_data_source( $data_source );
		}
	}

	public static function register_block_for_airtable_data_source( AirtableDataSource $data_source, array $block_overrides = [] ): void {
		register_remote_data_block(
			array_merge(
				[
					'title' => $data_source->get_display_name(),
					'queries' => [
						'display' => $data_source->___temp_get_query(),
						'list' => $data_source->___temp_get_list_query(),
					],
				],
				$block_overrides
			)
		);
	}

	public static function register_loop_block_for_airtable_data_source( AirtableDataSource $data_source, array $block_overrides = [] ): void {
		register_remote_data_block(
			array_merge(
				[
					'title' => sprintf( '%s Loop', $data_source->get_display_name() ),
					'loop' => true,
					'queries' => [
						'display' => $data_source->___temp_get_list_query(),
					],
				],
				$block_overrides
			)
		);
	}
}
