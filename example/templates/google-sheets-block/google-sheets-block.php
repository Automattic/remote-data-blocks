<?php

use RemoteDataBlocks\Integrations\Google\Sheets\GoogleSheetsDataSource;
use RemoteDataBlocks\Integrations\Google\Sheets\GoogleSheetsIntegration;

/**
 * Registers a remote data block representing a row from a Google Sheet. This
 * task can be completed in the plugin settings screen without writing code, but
 * this template shows how to register it programmatically -- possibly
 * customizing the fields and their mappings.
 *
 * Replace the placeholders with your Google configuration details.
 *
 * @see /docs/tutorials/google-sheets.md
 */
function register_google_sheets_remote_data_block(): void {
	// TODO: Replace the following placeholders with your actual values.
	$encoded_credentials = '{{ Base 64-encoded JSON credentials }}';
	$spreadsheet_id = '{{ Spreadsheet ID }}';
	$sheet_id = '{{ Sheet ID / GID }}'; // e.g., '1'
	$sheet_name = 'Houses';

	$credentials = json_decode( base64_decode( $encoded_credentials ), true );

	$westeros_houses_data_source = GoogleSheetsDataSource::from_array( [
		'service_config' => [
			'__version' => 1,
			'credentials' => $credentials,
			'display_name' => 'Westeros Houses',
			'spreadsheet' => [
				'id' => $spreadsheet_id,
			],
			'sheets' => [
				[
					'id' => $sheet_id,
					'name' => $sheet_name,
					// These mappings correspond to the columns of the table.
					'output_query_mappings' => [
						[
							'key' => 'row_id',
							'name' => 'Row ID',
							'path' => '$.RowId',
							'type' => 'id',
						],
						[
							'key' => 'house',
							'name' => 'House',
							'path' => '$.House',
							'type' => 'string',
						],
						[
							'key' => 'seat',
							'name' => 'Seat',
							'path' => '$.Seat',
							'type' => 'string',
						],
						[
							'key' => 'region',
							'name' => 'Region',
							'path' => '$.Region',
							'type' => 'string',
						],
						[
							'key' => 'words',
							'name' => 'Words',
							'path' => '$.Words',
							'type' => 'string',
						],
						[
							'key' => 'image_url',
							'name' => 'Sigil',
							'path' => '$.Sigil',
							'type' => 'image_url',
						],
					],
				],
			],
		],
	] );

	GoogleSheetsIntegration::register_blocks_for_google_sheets_data_source( $westeros_houses_data_source );
}
add_action( 'init', 'register_google_sheets_remote_data_block' );
