<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ArtInstituteOfChicago;

use function add_query_arg;

/**
 * Registers a remote data block representing an artwork from the Art Institute
 * of Chicago's public API.
 *
 * @see http://api.artic.edu/docs/
 */
function register_art_remote_data_block(): void {
	$aic_data_source = [
		'display_name' => 'Art Institute of Chicago',
		'endpoint' => 'https://api.artic.edu/api/v1/artworks',
		'request_headers' => [
			'Content-Type' => 'application/json',
		],
	];

	$get_art_query = [
		'data_source' => $aic_data_source,
		// Provide a callable (closure) to dynamically generate the endpoint using
		// the base endpoint from the data source and the input variables.
		'endpoint' => function ( array $input_variables ) use ( $aic_data_source ): string {
			$endpoint = add_query_arg( [
				'fields' => 'id,title,image_id,artist_title',
			], $aic_data_source['endpoint'] );

			if ( is_array( $input_variables['id'] ) ) {
				$ids = implode( ',', $input_variables['id'] );
			} else {
				$ids = $input_variables['id'];
			}

			if ( ! empty( $ids ) ) {
				return add_query_arg( [ 'ids' => $ids ], $endpoint );
			}

			return $endpoint;
		},
		'input_schema' => [
			'id' => [
				'name' => 'Art ID',
				'type' => 'id:list', // This type indicates that the input can be a single ID or a list of IDs.
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
				'image_url' => [
					'name' => 'Image URL',
					// Instead of a `path`, we provide a `generate` function to create the
					// image URL. The `$data` parameter contains the data returned from the
					// API at this "level" (e.g., after the root `path` has been applied).
					'generate' => static function ( $data ): string {
						return 'https://www.artic.edu/iiif/2/' . $data['image_id'] . '/full/843,/0/default.jpg';
					},
					'type' => 'image_url',
				],
			],
		],
	];

	$search_art_query = [
		'data_source' => $aic_data_source,
		// Provide a callable (closure) to dynamically generate the endpoint using
		// the base endpoint from the data source and the input variables.
		'endpoint' => function ( array $input_variables ) use ( $aic_data_source ): string {
			$endpoint = $aic_data_source['endpoint'] . '/search';
			$search_terms = $input_variables['search'] ?? '';

			if ( ! empty( $search_terms ) ) {
				$endpoint = add_query_arg( [ 'q' => $search_terms ], $endpoint . '/search' );
			}

			return add_query_arg( [
				'limit' => $input_variables['limit'],
				'fields' => 'id,title,image_id,artist_title',
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
				'name' => 'Items per page',
				'type' => 'ui:pagination_per_page',
			],
			'page' => [
				'default_value' => 1,
				'name' => 'Starting page',
				'type' => 'ui:pagination_page',
			],
		],
		// Reuse the output schema from `$get_art_query`.
		'output_schema' => $get_art_query['output_schema'],
		'pagination_schema' => [
			'total_items' => [
				'name' => 'Total items',
				'path' => '$.pagination.total',
				'type' => 'integer',
			],
		],
	];

	register_remote_data_block( [
		'title' => 'Art Institute of Chicago',
		'icon' => 'art',
		'render_query' => [
			'query' => $get_art_query,
		],
		'selection_queries' => [
			[
				'query' => $search_art_query,
				'type' => 'search',
			],
		],
	] );
}
add_action( 'init', __NAMESPACE__ . '\\register_art_remote_data_block' );
