<?php

namespace RemoteDataBlocks\Example\Airtable\EldenRingMap;

use RemoteDataBlocks\Integrations\Airtable\AirtableDatasource;
use RemoteDataBlocks\Logging\LoggerManager;

require_once __DIR__ . '/inc/queries/class-airtable-elden-ring-list-locations-query.php';
require_once __DIR__ . '/inc/queries/class-airtable-elden-ring-list-maps-query.php';

function register_airtable_elden_ring_map_block() {
	$block_name   = 'Elden Ring Location';
	$access_token = \RemoteDataBlocks\Example\get_access_token( 'airtable_elden_ring' );
	$base         = 'appqI3sJ9R2NcML8Y';
	$tables       = [
		'maps'      => 'tblS3OYo8tZOg04CP',
		'locations' => 'tblc82R9msH4Yh6ZX',
	];

	if ( empty( $access_token ) ) {
		$logger = LoggerManager::instance();
		$logger->warning( sprintf( '%s is not defined, cannot register %s block', 'EXAMPLE_AIRTABLE_ELDEN_RING_ACCESS_TOKEN', $block_name ) );
		return;
	}

	$elden_ring_datasource = AirtableDatasource::from_array( [
		'access_token' => $access_token,
		'base'         => $base,
		'tables'       => $tables,
	] );
	var_dump( $elden_ring_datasource );
	$list_locations_query = new AirtableEldenRingListLocationsQuery( $elden_ring_datasource );
	$list_maps_query      = new AirtableEldenRingListMapsQuery( $elden_ring_datasource );

	register_remote_data_block( $block_name, $list_locations_query );
	register_remote_data_list_query( $block_name, $list_maps_query );

	$block_pattern = file_get_contents( __DIR__ . '/inc/patterns/map-pattern.html' );
	register_remote_data_block_pattern( $block_name, 'remote-data-blocks/elden-ring-map/pattern', $block_pattern );

	$elden_ring_map_block_path = __DIR__ . '/build/blocks/elden-ring-map';
	wp_register_style( 'leaflet-style', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
	wp_register_script( 'leaflet-script', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );
	register_block_type( $elden_ring_map_block_path );
}
add_action( 'init', __NAMESPACE__ . '\\register_airtable_elden_ring_map_block' );
