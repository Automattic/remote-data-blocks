<?php

namespace RemoteDataBlocks\Integrations\Airtable;

use RemoteDataBlocks\Logging\LoggerManager;
use RemoteDataBlocks\WpdbStorage\DatasourceCRUD;

class AirtableIntegration {
	public static function init(): void {
		self::register_dynamic_data_source_blocks();
	}

	private static function register_dynamic_data_source_blocks(): void {
		$data_sources = DatasourceCRUD::get_data_sources( REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE );

		foreach ( $data_sources as $config ) {
			// Transform data to our experimental format, which is all array based
			$config = array_map(
				function ( $value ) {
					return is_object( $value ) ? (array) $value : $value;
				},
				(array) $config
			);
			self::register_blocks_for_airtable_data_source( $config );
		}
	}

	private static function register_blocks_for_airtable_data_source( array $config ): void {
		$logger = LoggerManager::instance();
		$logger->info( 'Registering Airtable block for: ' . wp_json_encode( $config ) ); // TODO: Remove this or make it debug level, etc.

		$base_id  = $config['base']['id'] ?? '';
		$table_id = $config['table']['id'] ?? '';
		if ( empty( $base_id ) ) {
			$logger->error( 'Airtable block is missing base ID' );
		}

		$airtable_datasource = new AirtableDatasource( $config['token'], $base_id, $table_id );
		// TODO: Block registration & all the rest...  This will only work if there is some mapping configured
	}
}
