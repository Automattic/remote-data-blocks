<?php

use RemoteDataBlocks\Integrations\Airtable\AirtableDataSource;
use RemoteDataBlocks\Integrations\Airtable\AirtableIntegration;

/**
 * Registers a remote data block representing a row from a Airtable base. This
 * task can be completed in the plugin settings screen without writing code, but
 * this template shows how to register it programmatically -- possibly
 * customizing the fields and their mappings.
 *
 * Replace the placeholders with your Airtable configuration details.
 */
function register_airtable_remote_data_block(): void {
	$airtable_data_source = AirtableDataSource::from_array( [
		'service_config' => [
			'__version' => 1,
			'access_token' => '{{ Access Token }}', // Airtable access token ("pat...")
			'base' => [
				'id' => '{{ Base ID }}', // Airtable base ID ("app...")
				'name' => 'Conference Events',
			],
			'display_name' => 'Conference Events',
			'tables' => [
				[
					'id' => '{{ Table ID }}', // Airtable table ID ("tbl...")
					'name' => 'Conference Events',
					// These mappings correspond to the columns of the table.
					'output_query_mappings' => [
						[
							'key' => 'record_id',
							'name' => 'ID',
							'path' => '$.id',
							'type' => 'id',
						],
						[
							'key' => 'title',
							'name' => 'Title',
							'path' => '$.fields.Activity',
							'type' => 'string',
						],
						[
							'key' => 'type',
							'name' => 'Type',
							'path' => '$.fields.Type',
							'type' => 'string',
						],
						[
							'key' => 'location',
							'name' => 'Location',
							'path' => '$.fields.Location',
							'type' => 'string',
						],
						[
							'key' => 'notes',
							'name' => 'Notes',
							'path' => '$.fields.Notes',
							'type' => 'string',
						],
					],
				],
			],
		],
	] );

	AirtableIntegration::register_blocks_for_airtable_data_source( $airtable_data_source );
}
add_action( 'init', 'register_airtable_remote_data_block' );
