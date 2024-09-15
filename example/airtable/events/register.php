<?php

namespace RemoteDataBlocks\Example\Airtable\Events;

use RemoteDataBlocks\Config\AirtableDatasource;
use RemoteDataBlocks\Editor\ConfigurationLoader;
use RemoteDataBlocks\Logging\LoggerManager;
use RemoteDataBlocks\REST\DatasourceCRUD;
use function add_action;

require_once __DIR__ . '/inc/queries/class-airtable-get-event-query.php';
require_once __DIR__ . '/inc/queries/class-airtable-list-events-query.php';

/**
 * Add Airtable Events datasource configuration.
 *
 * @param array $data_sources Existing data sources.
 * @return array Modified data sources.
 */
function add_airtable_events_datasource( array $data_sources ): array {
	$access_token = \RemoteDataBlocks\Example\get_access_token( 'airtable_events' );
	$base         = 'appVQ2PAl95wQSo9S';
	$table        = 'tblyGtuxblLtmoqMI';

	if ( empty( $access_token ) ) {
		return $data_sources;
	}

	return array_merge( $data_sources, [
		[
			'slug'    => 'airtable-events',
			'service' => 'airtable',
			'name'    => 'Airtable Events',
			'token'   => $access_token,
			'base'    => [
				'id'   => 'appVQ2PAl95wQSo9S',
				'name' => 'Events Base',
			],
			'table'   => [
				'id'   => 'tblyGtuxblLtmoqMI',
				'name' => 'Events Table',
			],
		],
	] );
}

add_filter( 'remote_data_blocks_data_sources', __NAMESPACE__ . '\\add_airtable_events_datasource' );


function register_airtable_events_block() {
	$block_name = 'Airtable Event';

	$config = DatasourceCRUD::get_data_source_by_slug( 'airtable-events' );

	$airtable_datasource        = new AirtableDatasource( $config['token'], $config['base']['id'], $config['table']['id'] );
	$airtable_get_event_query   = new AirtableGetEventQuery( $airtable_datasource );
	$airtable_list_events_query = new AirtableListEventsQuery( $airtable_datasource );

	ConfigurationLoader::register_block( $block_name, $airtable_get_event_query );
	ConfigurationLoader::register_list_query( $block_name, $airtable_list_events_query );
	ConfigurationLoader::register_loop_block( 'Airtable Event List', $airtable_list_events_query );
	ConfigurationLoader::register_page( $block_name, 'airtable-event' );
}

add_action( 'register_remote_data_blocks', __NAMESPACE__ . '\\register_airtable_events_block' );
