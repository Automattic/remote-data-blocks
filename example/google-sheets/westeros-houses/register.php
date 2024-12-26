<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\GoogleSheets\WesterosHouses;

use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Integrations\Google\Sheets\GoogleSheetsDataSource;

function register_westeros_houses_block(): void {
	$credentials = json_decode( base64_decode( \RemoteDataBlocks\Example\get_access_token( 'google_sheets_westeros_houses' ) ), true );
	$columns = [
		'House',
		'Seat',
		'Region',
		'Words',
		'Sigil',
	];

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
			'sheet' => [
				'id' => 1,
				'name' => 'Houses',
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
		'preprocess_response' => function ( mixed $response_data ) use ( $columns ): array {
			if ( isset( $response_data['values'] ) && is_array( $response_data['values'] ) ) {
				$values = $response_data['values'];
				array_shift( $values ); // Drop the first row

				$response_data['values'] = array_map(
					function ( $row, $index ) use ( $columns ) {
						$combined = array_combine( $columns, $row );
						$combined['RowId'] = $index + 1; // Add row_id field, starting from 1
						return $combined;
					},
					$values,
					array_keys( $values )
				);
			}

			return $response_data;
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
		'preprocess_response' => function ( mixed $response_data, array $input_variables ) use ( $columns ): array {
			$selected_row = null;
			$row_id = $input_variables['row_id'];

			if ( isset( $response_data['values'] ) && is_array( $response_data['values'] ) ) {
				$raw_selected_row = $response_data['values'][ $row_id ];
				if ( is_array( $raw_selected_row ) ) {
					$selected_row = array_combine( $columns, $raw_selected_row );
					$selected_row = array_combine( $columns, $selected_row );
					$selected_row['RowId'] = $row_id;
				}
			}

			return $selected_row;
		},
	] );

	register_remote_data_block( [
		'title' => 'Westeros House',
		'queries' => [
			'display' => $get_westeros_houses_query,
			'list' => $list_westeros_houses_query,
		],
		'query_input_overrides' => [
			[
				'query' => 'display',
				'source' => 'house',
				'source_type' => 'page',
				'target' => 'row_id',
				'target_type' => 'input_var',
			],
		],
		'pages' => [
			[
				'slug' => 'westeros-houses',
				'title' => 'Westeros Houses',
			],
		],
	] );

	register_remote_data_block( [
		'title' => 'Westeros Houses List',
		'loop' => true,
		'queries' => [
			'display' => $list_westeros_houses_query,
		],
	] );
}

add_action( 'init', __NAMESPACE__ . '\\register_westeros_houses_block' );
