<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Airtable\EldenRingMap;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Integrations\Airtable\AirtableDataSource;
use RemoteDataBlocks\Logging\LoggerManager;

require_once __DIR__ . '/inc/interactivity-store/interactivity-store.php';

function register_airtable_elden_ring_map_block(): void {
	$block_name = 'Elden Ring Location';
	$access_token = \RemoteDataBlocks\Example\get_access_token( 'airtable_elden_ring' );

	if ( empty( $access_token ) ) {
		$logger = LoggerManager::instance();
		$logger->warning( sprintf( '%s is not defined, cannot register %s block', 'EXAMPLE_AIRTABLE_ELDEN_RING_ACCESS_TOKEN', $block_name ) );
		return;
	}

	$elden_ring_data_source = AirtableDataSource::create( [
		'access_token' => $access_token,
		'base' => [
			'id' => 'appqI3sJ9R2NcML8Y',
			'name' => 'Elden Ring Locations',
		],
		'display_name' => 'Elden Ring Locations',
		'tables' => [], // AirtableDataSource does not formally provide queries.
	] );

	$list_locations_query = HttpQueryContext::from_array( [
		'endpoint' => function ( array $input_variables ) use ( $elden_ring_data_source ) {
			return $elden_ring_data_source()->get_endpoint() . '/tblc82R9msH4Yh6ZX?filterByFormula=FIND%28%27' . $input_variables['map_name'] . '%27%2C%20%7BMap%7D%29%3E0';
		},
		'input_schema' => [
			'map_name' => [
				'type' => 'string',
			],
		],
		'output_schema' => [
			'is_collection' => true,
			'path' => '$.records[*]',
			'type' => [
				'id' => [
					'name' => 'Location ID',
					'path' => '$.id',
					'type' => 'id',
				],
				'map_name' => [
					'name' => 'Name',
					'path' => '$.fields.Name',
					'type' => 'string',
				],
				'title' => [
					'name' => 'Name',
					'path' => '$.fields.Name',
					'type' => 'string',
				],
				'x' => [
					'name' => 'x',
					'path' => '$.fields.x',
					'type' => 'string',
				],
				'y' => [
					'name' => 'y',
					'path' => '$.fields.y',
					'type' => 'string',
				],
			],
		],
		'query_key' => 'example_elden_ring_list_locations',
		'query_name' => 'List locations',
	] );

	$list_maps_query = HttpQueryContext::from_array( [
		'endpoint' => $elden_ring_data_source()->get_endpoint() . '/tblS3OYo8tZOg04CP',
		'input_schema' => [
			'search' => [
				'type' => 'string',
			],
		],
		'output_schema' => [
			'is_collection' => true,
			'path' => '$.records[*]',
			'type' => [
				'id' => [
					'name' => 'Map ID',
					'path' => '$.id',
					'type' => 'id',
				],
				'map_name' => [
					'name' => 'Name',
					'path' => '$.fields.Name',
					'type' => 'string',
				],
			],
		],
		'query_key' => 'example_elden_ring_list_maps',
		'query_name' => 'List maps',
	] );

	register_remote_data_block( $block_name, $list_locations_query );
	register_remote_data_list_query( $block_name, $list_maps_query );

	$block_pattern = file_get_contents( __DIR__ . '/inc/patterns/map-pattern.html' );
	register_remote_data_block_pattern( $block_name, 'Elden Ring Map', $block_pattern, [ 'role' => 'inner_blocks' ] );

	$elden_ring_map_block_path = __DIR__ . '/build/blocks/elden-ring-map';
	wp_register_style( 'leaflet-style', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
	wp_register_script( 'leaflet-script', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );
	register_block_type( $elden_ring_map_block_path );
}
add_action( 'init', __NAMESPACE__ . '\\register_airtable_elden_ring_map_block' );
