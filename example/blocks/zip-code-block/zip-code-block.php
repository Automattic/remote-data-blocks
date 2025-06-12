<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ZipCode;

/**
 * Registers a remote data block for fetching zip code information from the
 * Zippopotam.us API.
 *
 * @see https://www.zippopotam.us/
 */
function register_zip_code_remote_data_block(): void {
	$zip_code_data_source = [
		'display_name' => 'Zip Code',
		'endpoint' => 'https://api.zippopotam.us/us/',
	];

	$zip_code_query = [
		'data_source' => $zip_code_data_source,
		// Provide a callable (closure) to dynamically generate the endpoint using
		// the base endpoint from the data source and the input variables.
		'endpoint' => function ( array $input_variables ) use ( $zip_code_data_source ): string {
			return $zip_code_data_source['endpoint'] . $input_variables['zip_code'];
		},
		'input_schema' => [
			'zip_code' => [
				'name' => 'Zip Code',
				'type' => 'string',
			],
		],
		'output_schema' => [
			'is_collection' => false, // This query returns a single record.
			'type' => [
				'zip_code' => [
					'name' => 'Zip Code',
					'path' => '$["post code"]', // JSON property with space requires brackets and quotes.
					'type' => 'string',
				],
				'city' => [
					'name' => 'City',
					'path' => '$.places[0]["place name"]', // JSON property with space requires brackets and quotes.
					'type' => 'string',
				],
				'state' => [
					'name' => 'State',
					'path' => '$.places[0].state',
					'type' => 'string',
				],
			],
		],
	];

	register_remote_data_block( [
		'title' => 'Zip Code',
		'render_query' => [
			'query' => $zip_code_query,
		],
	] );
}
add_action( 'init', __NAMESPACE__ . '\\register_zip_code_remote_data_block' );
