<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\E2E;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use WP_Error;

if ( ! function_exists( 'register_remote_data_block' ) ) {
	return;
}

final class PaginationFixtureQueryRunner extends QueryRunner {
	public function __construct( private string $mode ) {
		parent::__construct();
	}

	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Match the overridden signature.
	protected function get_raw_response_data( array $request_details, array $input_variables ): array|WP_Error {
		$rows = array_map( static fn ( int $id ): array => [
			'id' => (string) $id,
			'title' => sprintf( '%s %02d', 0 === $id % 2 ? 'Herb' : 'Tree', $id ),
		], range( 1, 50 ) );
		$search = $input_variables['search'] ?? '';
		$rows = array_values( array_filter( $rows, static fn ( array $row ): bool =>
			( ! isset( $input_variables['id'] ) || $row['id'] === $input_variables['id'] ) &&
			( '' === $search || false !== stripos( $row['title'], $search ) )
		) );
		$per_page = $input_variables['per_page'] ?? 10;
		$position = intval( $input_variables[ $this->mode ] ?? ( 'page' === $this->mode ? 1 : 0 ) );
		$offset = 'page' === $this->mode ? ( $position - 1 ) * $per_page : $position;
		$total = count( $rows );

		return [
			'input_variables' => $input_variables,
			'metadata' => [
				'status_code' => 200,
				'age' => 0,
			],
			'response_data' => [
				'rows' => array_slice( $rows, $offset, $per_page ),
				'total_items' => $total,
				// Cursor sources such as Airtable only return a forward cursor.
				'cursor_next' => $offset + $per_page < $total ? (string) ( $offset + $per_page ) : null,
			],
		];
	}
}

// Keep the actual parser, query pagination metadata and editor selection pipeline.
add_action( 'init', static function (): void {
	foreach ( [ 'page', 'offset', 'cursor' ] as $mode ) {
		$runner = new PaginationFixtureQueryRunner( $mode );
		$fields = [
			'id' => [
				'name' => 'Plant ID',
				'type' => 'id',
				'path' => '$.id',
			],
			'title' => [
				'name' => 'Plant title',
				'type' => 'title',
				'path' => '$.title',
			],
		];
		$query = [
			'data_source' => HttpDataSource::from_array( [
				'display_name' => 'Local pagination fixture',
				'endpoint' => 'https://example.invalid/never-requested',
			] ),
			'query_runner' => $runner,
			'endpoint' => static fn ( array $input_variables ): string => 'https://example.invalid/never-requested?' . http_build_query( $input_variables ),
			'input_schema' => [
				'id' => [
					'name' => 'Plant ID',
					'type' => 'id',
					'required' => true,
				],
			],
			'output_schema' => [
				'path' => '$.rows[0]',
				'type' => $fields,
			],
		];
		$selection_query = array_merge( $query, [
			'input_schema' => [
				'search' => [
					'name' => 'Search',
					'type' => 'ui:search_input',
					'default_value' => '',
				],
				'per_page' => [
					'name' => 'Page size',
					'type' => 'ui:pagination_per_page',
					'default_value' => 10,
				],
				$mode => [
					'name' => 'Position',
					'type' => 'ui:pagination_' . $mode,
					'default_value' => 'cursor' === $mode ? '' : ( 'page' === $mode ? 1 : 0 ),
				],
			],
			'output_schema' => [
				'path' => '$.rows[*]',
				'is_collection' => true,
				'type' => $fields,
			],
			'pagination_schema' => [
				'total_items' => [
					'name' => 'Total',
					'type' => 'integer',
					'path' => '$.total_items',
				],
				'cursor_next' => [
					'name' => 'Next',
					'type' => 'string',
					'path' => '$.cursor_next',
				],
			],
		] );
		register_remote_data_block( [
			'title' => 'Pagination ' . ucfirst( $mode ),
			'render_query' => [ 'query' => $query ],
			'selection_queries' => [
				[
					'query' => $selection_query,
					'type' => 'search',
				],
			],
		] );
	}
} );
