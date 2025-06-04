<?php

/**
 * Register a remote data block that uses a basic REST API. Customize the data
 * source, queries, and schemas to match your specific API requirements.
 */
function register_basic_rest_api_remote_data_block(): void {
	$api_data_source = [
		'display_name' => '{{ API Name }}',
		'endpoint' => '{{ API Base URL }}',
		'request_headers' => [
			'Content-Type' => 'application/json',
			// TODO: Add authentication headers, if needed.
			// 'Authorization' => 'Bearer {{ API token }}',
			// 'X-API-Key' => '{{ API key }}',
		],
	];

	// Get item query: Fetch one record by ID.
	$get_item_query = [
		'data_source' => $api_data_source,
		// Provide a callable (closure) to dynamically generate the endpoint using
		// the base endpoint from the data source and the input variables.
		'endpoint' => function ( array $input_variables ) use ( $api_data_source ): string {
			$endpoint = $api_data_source['endpoint'];
			$item_id = $input_variables['id'] ?? '';

			return $endpoint . '/items/' . $item_id;
		},
		'input_schema' => [
			'id' => [
				'name' => 'Item ID',
				'type' => 'id',
			],
		],
		'output_schema' => [
			// TODO: Adjust the field names, types, and paths based on your API
			// response structure.
			'is_collection' => false, // This query returns a single record.
			'path' => '$.data',
			'type' => [
				'id' => [
					'name' => 'ID',
					'type' => 'id',
					'path' => '$.id',
				],
				'title' => [
					'name' => 'Title',
					'type' => 'title',
					'path' => '$.title',
				],
				'description' => [
					'name' => 'Description',
					'type' => 'string',
					'path' => '$.description',
				],
				'image_url' => [
					'name' => 'Image URL',
					'type' => 'image_url',
					// Instead of a `path`, we provide a `generate` function to create the
					// image URL. The `$data` parameter contains the data returned from the
					// API at this "level" (e.g., after the root `path` has been applied).
					//
					// It also receives the raw response data, which can be useful if you
					// need to access input variables or other data not available in the
					// response.
					'generate' => static function ( array $data, array $raw_response_data ): string {
						$item_id = $data['id'] ?? $raw_response_data['input_variables']['id'];
						return 'https://example.com/images/items/' . $item_id . '.jpg';
					},
				],
				// TODO: Add more fields as needed.
			],
		],
	];

	// List items query: Fetch multiple records with pagination and search.
	$list_items_query = [
		'data_source' => $api_data_source,
		// Provide a callable (closure) to dynamically generate the endpoint using
		// the base endpoint from the data source and the input variables.
		'endpoint' => function ( array $input_variables ) use ( $api_data_source ): string {
			$endpoint = $api_data_source['endpoint'] . '/items';

			$query_params = [];

			// TODO: Apply pagination input variables according to your API or remove
			// if your API does not support pagination.
			if ( ! empty( $input_variables['limit'] ) ) {
				$query_params['limit'] = $input_variables['limit'];
			}

			if ( ! empty( $input_variables['page'] ) ) {
				$query_params['page'] = $input_variables['page'];
			}

			// TODO: Apply search input variable according to your API or remove if
			// your API does not support search.
			if ( ! empty( $input_variables['search'] ) ) {
				$query_params['q'] = $input_variables['search'];
			}

			return add_query_arg( $query_params, $endpoint );
		},
		'input_schema' => [
			'search' => [
				'name' => 'Search Terms',
				'type' => 'ui:search_input',
			],
			'limit' => [
				'default_value' => 10,
				'name' => 'Items per page',
				'type' => 'ui:pagination_per_page',
			],
			'page' => [
				'default_value' => 1,
				'name' => 'Page',
				'type' => 'ui:pagination_page',
			],
		],
		// Reuse the output schema from the single item query.
		'output_schema' => array_merge(
			$get_item_query['output_schema'],
			[ 'is_collection' => true ]
		),
		'pagination_schema' => [
			// TODO: Adjust the field names, types, and paths based on your API
			// response structure, or set `pagination_schema` to `null` if your API
			// does not support pagination.
			'total_items' => [
				'name' => 'Total Items',
				'path' => '$.meta.total',
			],
			'total_pages' => [
				'name' => 'Total Pages',
				'path' => '$.meta.total_pages',
			],
			'current_page' => [
				'name' => 'Current Page',
				'path' => '$.meta.current_page',
			],
		],
	];

	// Register the remote data block.
	register_remote_data_block( [
		'title' => '{{ Block name }}',
		'render_query' => [
			'query' => $get_item_query,
		],
		'selection_queries' => [
			[
				'query' => $list_items_query,
				'type' => 'search',
			],
		],
		// TODO: Uncomment and implement if you want to use a custom block pattern.
		// 'pattern' => file_get_contents( __DIR__ . '/patterns/default-pattern.html' ),
	] );
}
add_action( 'init', 'register_basic_rest_api_remote_data_block' );
