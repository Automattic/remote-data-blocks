<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Airtable\Events;

use RemoteDataBlocks\Integrations\Airtable\AirtableDataSource;
use RemoteDataBlocks\Integrations\Airtable\AirtableIntegration;

function register_airtable_events_block(): void {
	$access_token = \RemoteDataBlocks\Example\get_access_token( 'airtable_events' );

	if ( empty( $access_token ) ) {
		return;
	}

	$airtable_data_source = AirtableDataSource::from_array( [
		'service_config' => [
			'__version' => 1,
			'access_token' => $access_token,
			'base' => [
				'id' => 'appVQ2PAl95wQSo9S',
				'name' => 'Conference Events',
			],
			'display_name' => 'Conference Events',
			'tables' => [], // AirtableDataSource does not formally provide queries.
		],
	] );

	$block_options = [
		'pages' => [
			[
				'slug' => 'conference-event',
				'title' => 'Conference Events',
			],
		],
	];

	AirtableIntegration::register_block_for_airtable_data_source( $airtable_data_source, $block_options );
	AirtableIntegration::register_loop_block_for_airtable_data_source( $airtable_data_source, $block_options );
}

add_action( 'init', __NAMESPACE__ . '\\register_airtable_events_block' );
