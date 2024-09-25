<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Airtable;

use RemoteDataBlocks\Logging\LoggerManager;
use RemoteDataBlocks\WpdbStorage\DatasourceCrud;

class AirtableIntegration {
	public static function init(): void {
		$data_sources = DatasourceCrud::get_data_sources( REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE );

		foreach ( $data_sources as $config ) {
			self::register_blocks_for_airtable_data_source( $config );
		}
	}

	private static function register_blocks_for_airtable_data_source( array $config ): void {
		LoggerManager::instance()->info( 'Registering Airtable block for: ' . wp_json_encode( $config ) ); // TODO: Remove this or make it debug level, etc.

		// $airtable_datasource = AirtableDatasource::from_array( $config );

		// TODO: Block registration & all the rest...  This will only work if there is some mapping configured	}
}
