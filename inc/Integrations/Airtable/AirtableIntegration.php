<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Airtable;

use RemoteDataBlocks\Logging\LoggerManager;
use RemoteDataBlocks\WpdbStorage\DataSourceCrud;

class AirtableIntegration {
	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_blocks' ] );
	}

	public static function register_blocks(): void {
		$data_sources = DataSourceCrud::get_data_sources( REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE );

		foreach ( $data_sources as $config ) {
			self::register_blocks_for_airtable_data_source( $config );
		}
	}

	private static function register_blocks_for_airtable_data_source( array $config ): void {
		/** @var AirtableDataSource $airtable_data_source */
		$airtable_data_source = AirtableDataSource::from_array( $config );

		$block_name = $airtable_data_source->get_display_name();
		$query = $airtable_data_source->___temp_get_query();
		$list_query = $airtable_data_source->___temp_get_list_query();

		if ( is_wp_error( $query ) || is_wp_error( $list_query ) ) {
			LoggerManager::instance()->error( 'Failed to get query for Airtable block' );
			return;
		}

		register_remote_data_block( $block_name, $query );
		register_remote_data_list_query( $block_name, $list_query );

		LoggerManager::instance()->info( 'Registered Airtable block', [ 'block_name' => $block_name ] );
	}
}
