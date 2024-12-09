<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\GoogleSheets\WesterosHouses;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Integrations\Google\Sheets\GoogleSheetsDataSource;
use RemoteDataBlocks\Logging\LoggerManager;

function register_westeros_houses_block(): void {
	$block_name = 'Westeros House';
	$credentials = json_decode( base64_decode( \RemoteDataBlocks\Example\get_access_token( 'google_sheets_westeros_houses' ) ), true );
	$columns = [
		'House',
		'Seat',
		'Region',
		'Words',
		'Sigil',
	];

	if ( empty( $credentials ) ) {
		$logger = LoggerManager::instance();
		$logger->warning(
			sprintf(
				'%s is not defined, cannot register %s block',
				'EXAMPLE_GOOGLE_SHEETS_WESTEROS_HOUSES_ACCESS_TOKEN',
				$block_name
			)
		);
		return;
	}

	$westeros_houses_data_source = GoogleSheetsDataSource::create( [
		'display_name' => 'Westeros Houses',
		'spreadsheet' => [
			'id' => '1EHdQg53Doz0B-ImrGz_hTleYeSvkVIk_NSJCOM1FQk0',
			'name' => 'Houses',
		],
		'credentials' => $credentials,
	] );

	$list_westeros_houses_query = HttpQueryContext::from_array( [
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
		'preprocess_response' => function ( array $response_data ) use ( $columns ): array {
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

	$get_westeros_houses_query = HttpQueryContext::from_array( [
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
		'preprocess_response' => function ( array $response_data ) use ( $columns ): array {
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

	register_remote_data_block( $block_name, $get_westeros_houses_query );
	register_remote_data_list_query( $block_name, $list_westeros_houses_query );
	register_remote_data_loop_block( 'Westeros Houses List', $list_westeros_houses_query );
	register_remote_data_page( $block_name, 'westeros-houses' );
}

add_action( 'init', __NAMESPACE__ . '\\register_westeros_houses_block' );
