<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\OpenLibrary;

use function _n;
use function add_query_arg;

/**
 * Registers a remote data block representing book information from the Open Library API.
 * This block allows users to search for books and display detailed information including
 * title, author, publication date, and cover image.
 *
 * @see https://openlibrary.org/dev/docs/api/search
 */
function register_open_library_remote_data_block(): void {
	$open_library_data_source = [
		'display_name' => 'Open Library',
		'endpoint' => add_query_arg( [ 'fields' => 'key,title,author_name,first_publish_year,cover_i,edition_count' ], 'https://openlibrary.org/search.json' ),
		'request_headers' => [
			'Content-Type' => 'application/json',
		],
	];

	$get_book_query = [
		'data_source' => $open_library_data_source,
		'display_name' => 'Get book details',
		'endpoint' => function ( array $input_variables ) use ( $open_library_data_source ): string {
			$work_key = $input_variables['work_key'] ?? '';
			return add_query_arg( [ 'q' => $work_key ], $open_library_data_source['endpoint'] );
		},
		'input_schema' => [
			'work_key' => [
				'name' => 'Work Key',
				'required' => true,
				'type' => 'id',
			],
		],
		'output_schema' => [
			'is_collection' => false,
			'path' => '$.docs[0]',
			'type' => [
				'work_key' => [
					'name' => 'Work Key',
					'path' => '$.key',
					'type' => 'id',
				],
				'title' => [
					'name' => 'Title',
					'path' => '$.title',
					'type' => 'title',
				],
				'author_name' => [
					'name' => 'Author',
					'path' => '$.author_name[0]',
					'type' => 'string',
				],
				'first_publish_year' => [
					'name' => 'First Published',
					'path' => '$.first_publish_year',
					'default_value' => 'Unknown',
					'type' => 'string',
				],
				'cover_image_url' => [
					'name' => 'Cover Image',
					'generate' => static function ( array $data ): string {
						$cover_id = $data['cover_i'] ?? null;

						if ( $cover_id ) {
							return "https://covers.openlibrary.org/b/id/{$cover_id}-M.jpg";
						}

						return '';
					},
					'type' => 'image_url',
				],
				'edition_count' => [
					'name' => 'Editions',
					'path' => '$.edition_count',
					'default_value' => 0,
					'format' => static function ( $value ): string {
						$count = is_numeric( $value ) ? (int) $value : 0;
						/* translators: %d is the number of book editions */
						return sprintf( _n( '%d edition', '%d editions', $count ), $count );
					},
					'type' => 'string',
				],
			],
		],
	];

	$search_books_query = [
		'data_source' => $open_library_data_source,
		'display_name' => 'Search books',
		'endpoint' => function ( array $input_variables ) use ( $open_library_data_source ): string {
			$search_terms = $input_variables['search'] ?? '';
			$limit = $input_variables['limit'] ?? 10;
			$page = $input_variables['page'] ?? 1;

			$offset = ( $page - 1 ) * $limit;

			$query_params = [
				'limit' => $limit,
				'offset' => $offset,
				'fields' => 'key,title,author_name,first_publish_year,cover_i,edition_count',
			];

			if ( ! empty( $search_terms ) ) {
				$query_params['q'] = $search_terms;
			}

			return add_query_arg( $query_params, $open_library_data_source['endpoint'] );
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
		'output_schema' => [
			'is_collection' => true,
			'path' => '$.docs[*]',
			'type' => [
				'work_key' => [
					'name' => 'Work Key',
					'path' => '$.key',
					'type' => 'id',
				],
				'title' => [
					'name' => 'Title',
					'path' => '$.title',
					'type' => 'title',
				],
				'author_name' => [
					'name' => 'Authors',
					'path' => '$.author_name[0]',
					'type' => 'string',
				],
				'first_publish_year' => [
					'name' => 'First Published',
					'path' => '$.first_publish_year',
					'default_value' => 'Unknown',
					'type' => 'string',
				],
				'cover_image_url' => [
					'name' => 'Cover Image',
					'generate' => static function ( array $data ): string {
						$cover_id = $data['cover_i'] ?? null;

						if ( $cover_id ) {
							return "https://covers.openlibrary.org/b/id/{$cover_id}-M.jpg";
						}

						return '';
					},
					'type' => 'image_url',
				],
				'edition_count' => [
					'name' => 'Editions',
					'path' => '$.edition_count',
					'default_value' => 0,
					'format' => static function ( $value ): string {
						$count = is_numeric( $value ) ? (int) $value : 0;
						/* translators: %d is the number of book editions */
						return sprintf( _n( '%d edition', '%d editions', $count ), $count );
					},
					'type' => 'string',
				],
			],
		],
		'pagination_schema' => [
			'total_items' => [
				'name' => 'Total items',
				'path' => '$.numFound',
				'type' => 'integer',
			],
		],
	];

	register_remote_data_block( [
		'title' => 'Open Library Book',
		'icon' => 'book',
		'render_query' => [
			'query' => $get_book_query,
		],
		'selection_queries' => [
			[
				'query' => $search_books_query,
				'type' => 'search',
			],
		],
		'patterns' => [
			[
				'title' => 'Book Details Layout',
				'html' => file_get_contents( __DIR__ . '/patterns/book-pattern.html' ),
				'role' => 'inner_blocks', // Bypass the pattern selection step.
			],
		],
	] );
}
add_action( 'init', __NAMESPACE__ . '\\register_open_library_remote_data_block' );
