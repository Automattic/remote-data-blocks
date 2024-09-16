<?php

namespace RemoteDataBlocks\Example\GoogleSheets\WesterosHouses;

use RemoteDataBlocks\Editor\BlockManagement\ConfigRegistry;
use RemoteDataBlocks\Logging\LoggerManager;
use function add_action;

require_once __DIR__ . '/inc/queries/class-westeros-houses-datasource.php';
require_once __DIR__ . '/inc/queries/class-list-westeros-houses-query.php';
require_once __DIR__ . '/inc/queries/class-get-westeros-houses-query.php';

function register_westeros_houses_block() {
	$block_name   = 'Westeros House';
	$access_token = \RemoteDataBlocks\Example\get_access_token( 'google_sheets_westeros_houses' );

	if ( empty( $access_token ) ) {
		$logger = LoggerManager::instance();
		$logger->warning(
			sprintf(
				'%s is not defined, cannot register %s block',
				'EXAMPLE_GOOGLE_SHEETS_WESTEROS_HOUSES_ACCESS_TOKEN',
				$block_name
			)
		);
		return;
	}

	$westeros_houses_datasource = new WesterosHousesDatasource( $access_token );
	$list_westeros_houses_query = new ListWesterosHousesQuery( $westeros_houses_datasource );
	$get_westeros_houses_query  = new GetWesterosHousesQuery( $westeros_houses_datasource );

	ConfigRegistry::register_block( $block_name, $get_westeros_houses_query );
	ConfigRegistry::register_list_query( $block_name, $list_westeros_houses_query );
	ConfigRegistry::register_loop_block( 'Westeros Houses List', $list_westeros_houses_query );
	ConfigRegistry::register_page( $block_name, 'westeros-houses' );
}

add_action( 'register_remote_data_blocks', __NAMESPACE__ . '\\register_westeros_houses_block' );
