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
		/** @var AirtableDatasource $airtable_datasource */
		$airtable_datasource = AirtableDatasource::from_array( $config );

		$block_name = $airtable_datasource->get_display_name();
		$query      = $airtable_datasource->__temp_get_query();

		if ( is_wp_error( $query ) ) {
			LoggerManager::instance()->error( 'Failed to get query for Airtable block' );
			return;
		}

		register_remote_data_block( $block_name, $query );
		register_remote_data_list_query( $block_name, $query );
		
		LoggerManager::instance()->info( 'Registered Airtable block', [ 'block_name' => $block_name ] );
	}
}
