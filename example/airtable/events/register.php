<?php

namespace RemoteDataBlocks\Example\Airtable\Events;

use RemoteDataBlocks\Editor\BlockManagement\ConfigurationLoader;
use RemoteDataBlocks\Integrations\Airtable\AirtableDatasource;
use RemoteDataBlocks\Logging\LoggerManager;
use function add_action;

require_once __DIR__ . '/inc/queries/class-airtable-get-event-query.php';
require_once __DIR__ . '/inc/queries/class-airtable-list-events-query.php';

function register_airtable_events_block() {
	$block_name   = 'Airtable Event';
	$access_token = \RemoteDataBlocks\Example\get_access_token( 'airtable_events' );
	$base         = 'appVQ2PAl95wQSo9S';
	$table        = 'tblyGtuxblLtmoqMI';

	if ( empty( $access_token ) ) {
		$logger = LoggerManager::instance();
		$logger->warning( sprintf( '%s is not defined, cannot register %s block', 'EXAMPLE_AIRTABLE_EVENTS_ACCESS_TOKEN', $block_name ) );
		return;
	}

	$airtable_datasource        = new AirtableDatasource( $access_token, $base, $table );
	$airtable_get_event_query   = new AirtableGetEventQuery( $airtable_datasource );
	$airtable_list_events_query = new AirtableListEventsQuery( $airtable_datasource );

	ConfigurationLoader::register_block( $block_name, $airtable_get_event_query );
	ConfigurationLoader::register_list_query( $block_name, $airtable_list_events_query );
	ConfigurationLoader::register_loop_block( 'Airtable Event List', $airtable_list_events_query );
	ConfigurationLoader::register_page( $block_name, 'airtable-event' );
}
add_action( 'register_remote_data_blocks', __NAMESPACE__ . '\\register_airtable_events_block' );
