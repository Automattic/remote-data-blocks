<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\GoogleSheets\WesterosHouses;

use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Integrations\Google\Sheets\GoogleSheetsDataSource;

function register_westeros_houses_block(): void {
	$credentials = json_decode( base64_decode( \RemoteDataBlocks\Example\get_access_token( 'google_sheets_westeros_houses' ) ), true );

	if ( empty( $credentials ) ) {
		return;
	}

	$westeros_houses_data_source = GoogleSheetsDataSource::from_array( [
		'service_config' => [
			'__version' => 1,
			'credentials' => $credentials,
			'display_name' => 'Westeros Houses',
			'spreadsheet' => [
				'id' => '1EHdQg53Doz0B-ImrGz_hTleYeSvkVIk_NSJCOM1FQk0',
			],
			'sheets' => [
				[
					'id' => '1',
					'name' => 'Houses',
					'output_query_mappings' => [],
				],
			],
		],
	] );

	$list_westeros_houses_query = HttpQuery::from_array( [
		'data_source' => $westeros_houses_data_source,
		'endpoint' => $westeros_houses_data_source->get_endpoint() . '/values/Houses',
		'output_schema' => [
			'is_collection' => true,
			'path' => '$.values[*]',
			'type' => [
				'row_id' => [
					'name' => 'Row ID',
					'path' => '$.RowId',
					'type' => 'id',
				],
				'house' => [
					'name' => 'House',
					'path' => '$.House',
					'type' => 'string',
				],
				'seat' => [
					'name' => 'Seat',
					'path' => '$.Seat',
					'type' => 'string',
				],
				'region' => [
					'name' => 'Region',
					'path' => '$.Region',
					'type' => 'string',
				],
				'words' => [
					'name' => 'Words',
					'path' => '$.Words',
					'type' => 'string',
				],
				'image_url' => [
					'name' => 'Sigil',
					'path' => '$.Sigil',
					'type' => 'image_url',
				],
			],
		],
		'preprocess_response' => function ( mixed $response_data ): array {
			return GoogleSheetsDataSource::preprocess_list_response( $response_data );
		},
	] );

	$get_westeros_houses_query = HttpQuery::from_array( [
		'data_source' => $westeros_houses_data_source,
		'endpoint' => $westeros_houses_data_source->get_endpoint() . '/values/Houses',
		'input_schema' => [
			'row_id' => [
				'name' => 'Row ID',
				'type' => 'id',
			],
		],
		'output_schema' => [
			'type' => [
				'row_id' => [
					'name' => 'Row ID',
					'path' => '$.RowId',
					'type' => 'id',
				],
				'house' => [
					'name' => 'House',
					'path' => '$.House',
					'type' => 'string',
				],
				'seat' => [
					'name' => 'Seat',
					'path' => '$.Seat',
					'type' => 'string',
				],
				'region' => [
					'name' => 'Region',
					'path' => '$.Region',
					'type' => 'string',
				],
				'words' => [
					'name' => 'Words',
					'path' => '$.Words',
					'type' => 'string',
				],
				'image_url' => [
					'name' => 'Sigil',
					'path' => '$.Sigil',
					'type' => 'image_url',
				],
			],
		],
		'preprocess_response' => function ( mixed $response_data, array $input_variables ): array {
			return GoogleSheetsDataSource::preprocess_get_response( $response_data, $input_variables );
		},
	] );

	register_remote_data_block( [
		'title' => 'Westeros House',
		'render_query' => [
			'query' => $get_westeros_houses_query,
		],
		'selection_queries' => [
			[
				'query' => $list_westeros_houses_query,
				'type' => 'list',
			],
		],
		'overrides' => [
			[
				'display_name' => 'Use Westeros House from URL',
				'name' => 'westeros_house',
			],
		],
	] );

	register_remote_data_block( [
		'title' => 'Westeros Houses List',
		'render_query' => [
			'loop' => true,
			'query' => $list_westeros_houses_query,
		],
	] );

	// A page with slug "westeros-houses" must be created.
	add_rewrite_rule( '^westeros-houses/([^/]+)/?', 'index.php?pagename=westeros-houses&row_id=$matches[1]', 'top' );

	add_filter( 'query_vars', function ( array $query_vars ): array {
		$query_vars[] = 'row_id';
		return $query_vars;
	}, 10, 1 );

	add_filter( 'remote_data_blocks_query_input_variables', function ( array $input_variables, array $enabled_overrides ): array {
		if ( true === in_array( 'westeros_house', $enabled_overrides, true ) ) {
			$row_id = get_query_var( 'row_id' );

			if ( ! empty( $row_id ) ) {
				$input_variables['row_id'] = $row_id;
			}
		}

		return $input_variables;
	}, 10, 2 );
}

add_action( 'init', __NAMESPACE__ . '\\register_westeros_houses_block' );
