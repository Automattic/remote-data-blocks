<?php declare(strict_types = 1);

/**
 * Plugin Name: Art Institute RDB Example
 * Description: Creates a custom block to be used with Remote Data Blocks in order to retrieve artwork from the Art Institute of Chicago.
 * Author: WPVIP
 * Author URI: https://remotedatablocks.com/
 * Text Domain: remote-data-blocks
 * Version: 1.0.0
 * Requires Plugins: remote-data-blocks
 */

namespace RemoteDataBlocks\Example\ArtInstituteOfChicago;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;
use function add_query_arg;

function register_aic_block(): void {
	$aic_data_source = HttpDataSource::from_array( [
		'display_name' => 'Art Institute of Chicago',
		'endpoint' => 'https://api.artic.edu/api/v1/artworks',
		'request_headers' => [
			'Content-Type' => 'application/json',
		],
	] );

	$get_art_query = HttpQuery::from_array([
		'data_source' => $aic_data_source,
		'endpoint' => function ( array $input_variables ) use ( $aic_data_source ): string {
			$endpoint = $aic_data_source->get_endpoint();

			if ( is_array( $input_variables['id'] ) ) {
				$ids = implode( ',', $input_variables['id'] );
			} else {
				$ids = $input_variables['id'];
			}

			if ( ! empty( $ids ) ) {
				return add_query_arg([
					'ids' => $ids,
					'fields' => 'id,title,image_id,artist_title',
				], $endpoint );
			}

			return $endpoint;
		},
		'input_schema' => [
			'id' => [
				'name' => 'Art ID',
				'type' => 'id:list',
			],
		],
		'output_schema' => [
			'is_collection' => true,
			'path' => '$.data[*]',
			'type' => [
				'id' => [
					'name' => 'Art ID',
					'type' => 'id',
					'path' => '$.id',
				],
				'artist_title' => [
					'name' => 'Artist Title',
					'type' => 'string',
					'path' => '$.artist_title',
				],
				'title' => [
					'name' => 'Title',
					'type' => 'title',
					'path' => '$.title',
				],
				'image_id' => [
					'name' => 'Image ID',
					'type' => 'id',
					'path' => '$.image_id',
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
		'required_query' => 'search_art',
		'type' => 'display',
	]);

	$collection_query = HttpQuery::from_array([
		'data_source' => $aic_data_source,
		'endpoint' => function ( array $input_variables ) use ( $aic_data_source ): string {
			$endpoint = $aic_data_source->get_endpoint();
			return add_query_arg( [
				'limit' => $input_variables['limit'],
				'fields' => 'id,title,image_id,artist_title',
				'page' => $input_variables['page'],
			], $endpoint );
		},
		'input_schema' => [
			'limit' => [
				'default_value' => 10,
				'name' => 'Items per page',
				'type' => 'ui:pagination_per_page',
			],
			'page' => [
				'default_value' => 1,
				'name' => 'Starting page',
				'type' => 'ui:pagination_page',
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
				'artist_title' => [
					'name' => 'Artist Title',
					'type' => 'string',
					'path' => '$.artist_title',
				],
				'title' => [
					'name' => 'Title',
					'type' => 'title',
					'path' => '$.title',
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
		'type' => 'display',
	]);

	$search_art_query = HttpQuery::from_array([
		'data_source' => $aic_data_source,
		'endpoint' => function ( array $input_variables ) use ( $aic_data_source ): string {
			$endpoint = $aic_data_source->get_endpoint();
			$search_terms = $input_variables['search'] ?? '';

			if ( ! empty( $search_terms ) ) {
				$endpoint = add_query_arg( [ 'q' => $search_terms ], $endpoint . '/search' );
			}

			return add_query_arg( [
				'limit' => $input_variables['limit'],
				'page' => $input_variables['page'],
			], $endpoint );
		},
		'input_schema' => [
			'search' => [
				'name' => 'Search terms',
				'type' => 'ui:search_input',
			],
			'limit' => [
				'default_value' => 10,
				'name' => 'Pagination limit',
				'type' => 'ui:pagination_per_page',
			],
			'page' => [
				'default_value' => 1,
				'name' => 'Pagination page',
				'type' => 'ui:pagination_page',
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
				'artist_title' => [
					'name' => 'Artist Title',
					'type' => 'string',
					'path' => '$.artist_title',
				],
				'title' => [
					'name' => 'Title',
					'type' => 'title',
					'path' => '$.title',
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
		'pagination_schema' => [
			'total_items' => [
				'name' => 'Total items',
				'path' => '$.pagination.total',
				'type' => 'integer',
			],
		],
		'type' => 'search',
	]);

	register_remote_data_block( [
		'title' => 'Art Institute of Chicago',
		'icon' => 'art',
		'instructions' => 'This block displays a set amount of artworks based on the provided limit.',
		'queries' => [
			// Changing the name of this query to anything but display will break existing blocks content.
			'get_art' => [ 
				'query' => $get_art_query,
				'name' => 'Get Art',
				'inputs' => [
					'id' => [
						'name' => 'Artwork',
						'sources' => [
							[ 'display_name' => 'Search Art', 'type' => 'id:list', 'query_key' => 'search_art' ],
							[ 'display_name' => 'Manual ID', 'type' => 'manual', 'data_type' => 'string' ],
						]
					]
				] 
			],
			'search_art' => [
				'query' => $search_art_query,
				'name' => 'Search Art',
				'inputs' => [
					'search' => [
						'name' => 'Search',
						'sources' => [
							[ 'display_name' => 'Manual Input', 'type' => 'manual', 'data_type' => 'string' ],
						]
					],
					'category' => [
						'name' => 'Category',
						'sources' => [
							[ 'display_name' => 'Pick from list', 'type' => 'id:list', 'query_key' => 'search_art' ],
							[ 'display_name' => 'Manual Input', 'type' => 'manual', 'data_type' => 'string' ],
						]
					],
					'limit' => [
						'name' => 'Limit',
						'sources' => [
							[ 'display_name' => 'Manual Input', 'type' => 'manual', 'data_type' => 'number' ],
						]
					]
				] 
			],
		],
		'display_queries' => [ 'get_art', 'search_art' ],
	] );

	// register_remote_data_block( [
	// 	'title' => 'Art Institute of Chicago',
	// 	'icon' => 'art',
	// 	'queries' => [
	// 		// Changing the name of this query to anything but display will break existing blocks content.
	// 		'display' => $get_art_query,
	// 		'search_art' => $search_art_query,
	// 	],
	// ] );
}
add_action( 'init', __NAMESPACE__ . '\\register_aic_block' );
