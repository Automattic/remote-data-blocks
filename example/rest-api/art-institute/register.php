<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ArtInstituteOfChicago;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;
use function add_query_arg;

function register_aic_block(): void {
	$aic_data_source = HttpDataSource::from_array( [
		'service' => 'art-institute',
		'service_config' => [
			'__version' => 1,
			'display_name' => 'Art Institute of Chicago',
			'endpoint' => 'https://api.artic.edu/api/v1/artworks',
			'request_headers' => [
				'Content-Type' => 'application/json',
			],
		],
	] );

	$get_art_query = HttpQuery::from_array( [
		'data_source' => $aic_data_source,
		'endpoint' => function ( array $input_variables ) use ( $aic_data_source ): string {
			return sprintf( '%s/%s', $aic_data_source->get_endpoint(), $input_variables['id'] ?? '' );
		},
		'input_schema' => [
			'id' => [
				'name' => 'Art ID',
				'type' => 'id',
			],
		],
		'output_schema' => [
			'is_collection' => false,
			'path' => '$.data',
			'type' => [
				'id' => [
					'name' => 'Art ID',
					'type' => 'id',
				],
				'title' => [
					'name' => 'Title',
					'type' => 'string',
				],
				'image_id' => [
					'name' => 'Image ID',
					'type' => 'id',
				],
				'image_url' => [
					'name' => 'Image URL',
					'generate' => function ( $data ): string {
						return 'https://www.artic.edu/iiif/2/' . $data['image_id'] . '/full/843,/0/default.jpg';
					},
					'type' => 'image_url',
				],
			],
		],
	] );

	$search_art_query = HttpQuery::from_array( [
		'data_source' => $aic_data_source,
		'endpoint' => function ( array $input_variables ) use ( $aic_data_source ): string {
			$query = $input_variables['search_terms'];
			$endpoint = $aic_data_source->get_endpoint() . '/search';

			return add_query_arg( [ 'q' => $query ], $endpoint );
		},
		'input_schema' => [
			'search_terms' => [
				'name' => 'Search Terms',
				'type' => 'string',
			],
		],
		'output_schema' => [
			'is_collection' => true,
			'path' => '$.data[*]',
			'type' => [
				'id' => [
					'name' => 'Art ID',
					'type' => 'id',
				],
				'title' => [
					'name' => 'Title',
					'type' => 'string',
				],
			],
		],
	] );

	register_remote_data_block( [
		'title' => 'Art Institute of Chicago',
		'queries' => [
			'display' => $get_art_query,
			'search' => $search_art_query,
		],
	] );
}
add_action( 'init', __NAMESPACE__ . '\\register_aic_block' );
