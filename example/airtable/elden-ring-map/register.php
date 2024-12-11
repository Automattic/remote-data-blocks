<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Airtable\EldenRingMap;

use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Integrations\Airtable\AirtableDataSource;

require_once __DIR__ . '/inc/interactivity-store/interactivity-store.php';

function register_airtable_elden_ring_map_block(): void {
	$block_name = 'Elden Ring Location';
	$access_token = \RemoteDataBlocks\Example\get_access_token( 'airtable_elden_ring' );

	if ( empty( $access_token ) ) {
		return;
	}

	$elden_ring_data_source = AirtableDataSource::from_array( [
		'service_config' => [
			'__version' => 1,
			'access_token' => $access_token,
			'base' => [
				'id' => 'appqI3sJ9R2NcML8Y',
				'name' => 'Elden Ring Locations',
			],
			'display_name' => 'Elden Ring Locations',
			'tables' => [], // AirtableDataSource does not formally provide queries.
		],
	] );

	$list_locations_query = HttpQuery::from_array( [
		'data_source' => $elden_ring_data_source,
		'endpoint' => function ( array $input_variables ) use ( $elden_ring_data_source ) {
			return $elden_ring_data_source->get_endpoint() . '/tblc82R9msH4Yh6ZX?filterByFormula=FIND%28%27' . $input_variables['map_name'] . '%27%2C%20%7BMap%7D%29%3E0';
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
	] );

	$list_maps_query = HttpQuery::from_array( [
		'data_source' => $elden_ring_data_source,
		'endpoint' => $elden_ring_data_source->get_endpoint() . '/tblS3OYo8tZOg04CP',
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
	] );

	register_remote_data_block( [
		'title' => $block_name,
		'queries' => [
			'display' => $list_locations_query,
			'list' => $list_maps_query,
		],
		'patterns' => [
			[
				'title' => 'Elden Ring Map',
				'html' => file_get_contents( __DIR__ . '/inc/patterns/map-pattern.html' ),
				'role' => 'inner_blocks',
			],
		],
	] );

	$elden_ring_map_block_path = __DIR__ . '/build/blocks/elden-ring-map';
	wp_register_style( 'leaflet-style', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
	wp_register_script( 'leaflet-script', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );
	register_block_type( $elden_ring_map_block_path );
}
add_action( 'init', __NAMESPACE__ . '\\register_airtable_elden_ring_map_block' );
