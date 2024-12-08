<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ZipCode;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;

function register_zipcode_block(): void {
	if ( ! defined( 'REMOTE_DATA_BLOCKS_EXAMPLE_ZIP_CODE_DATA_SOURCE_UUID' ) ) {
		return;
	}

	$zipcode_data_source = HttpDataSource::from_uuid( REMOTE_DATA_BLOCKS_EXAMPLE_ZIP_CODE_DATA_SOURCE_UUID );

	if ( ! $zipcode_data_source instanceof HttpDataSource ) {
		return;
	}

	$zipcode_query = HttpQuery::from_array( [
		'data_source' => $zipcode_data_source,
		'endpoint' => function ( array $input_variables ) use ( $zipcode_data_source ): string {
			return $zipcode_data_source->get_endpoint() . $input_variables['zip_code'];
		},
		'input_schema' => [
			'zip_code' => [
				'name' => 'Zip Code',
				'type' => 'string',
			],
		],
		'output_schema' => [
			'is_collection' => false,
			'type' => [
				'zip_code' => [
					'name' => 'Zip Code',
					'path' => '$["post code"]',
					'type' => 'string',
				],
				'city' => [
					'name' => 'City',
					'path' => '$.places[0]["place name"]',
					'type' => 'string',
				],
				'state' => [
					'name' => 'State',
					'path' => '$.places[0].state',
					'type' => 'string',
				],
			],
		],
	] );

	register_remote_data_block( [
		'title' => 'Zip Code',
		'queries' => [
			'display' => $zipcode_query,
		],
	] );
}
add_action( 'init', __NAMESPACE__ . '\\register_zipcode_block' );
