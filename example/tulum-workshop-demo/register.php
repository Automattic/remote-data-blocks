<?php declare(strict_types = 1);
/**
 * Plugin Name:       Tulum Workshop Demo
 * Plugin URI:        https://wordpress.org/plugins/tulum-workshop-demo
 * Description:       Demo plugin for Tulum Workshop.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            WPVIP
 * Author URI:        https://wpvip.com
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tulum-workshop-demo
 *
 * @package tulum-workshop-demo
 */

namespace TulumWorkshopDemo;

use RemoteDataBlocks\Integrations\Airtable\AirtableDataSource;
use RemoteDataBlocks\WpdbStorage\DataSourceCrud;
use RemoteDataBlocks\Integrations\Airtable\AirtableGetItemQuery;
use RemoteDataBlocks\Integrations\Airtable\AirtableListItemsQuery;

// require_once __DIR__ . '/inc/queries/class-airtable-get-event-query.php';
// require_once __DIR__ . '/inc/queries/class-airtable-list-events-query.php';

function register_airtable_events_block() {
	$airtable_data_source_config = DataSourceCrud::get_by_slug( 'airtable-tulum' );

	if ( ! $airtable_data_source_config ) {
		return;
	}

	$airtable_data_source = AirtableDataSource::from_array( $airtable_data_source_config );

	$input_schema = [
		'record_id' => [
			'name' => 'Record ID',
			'overrides' => [
				[
					'target'  => '_rid',
					'type'    => 'query_var',
				],
			],
			'type' => 'id',
		],
	];

	$output_schema = [
		'is_collection' => false,
		'mappings'      => [
			'id' => [
				'name' => 'Record ID',
				'path' => '$.id',
				'type' => 'id',
			],
			'name'    => [
				'name' => 'Name',
				'path' => '$.fields.Name',
				'type' => 'string',
			],
			'title' => [
				'name' => 'Title',
				'path' => '$.fields.Title',
				'type' => 'string',
			],
			'description' => [
				'name' => 'Description',
				'path' => '$.fields.Description',
				'type' => 'string',
			],
			'photo'     => [
				'name' => 'Photo URL',
				'path' => '$.fields.Photo[0].url',
				'type' => 'image_url',
			],
		],
	];


	$airtable_get_event_query = AirtableGetItemQuery::from_array([
		'data_source'   => $airtable_data_source,
		'input_schema'  => $input_schema,
		'output_schema' => $output_schema,
	]);

	$airtable_list_events_query = get_list_query( $airtable_data_source_config, $airtable_data_source );

	$block_name = 'Tulum Workshop Event';
	register_remote_data_block( $block_name, $airtable_get_event_query );
	register_remote_data_list_query( $block_name, $airtable_list_events_query );
	register_remote_data_loop_block( 'Tulum Workshop Events', $airtable_list_events_query );
	register_remote_data_page( $block_name, 'tulum-details' );
}

function get_list_query( $config, $airtable_data_source ) {
	$output_schema = [
		'root_path'     => '$.records[*]',
		'is_collection' => true,
		'mappings'      => [
			'record_id' => [
				'name' => 'Record ID',
				'path' => '$.id',
				'type' => 'id',
			],
			'name'        => [
				'name' => 'Name',
				'path' => '$.fields.Name',
				'type' => 'string',
			],
			'title'       => [
				'name' => 'Title',
				'path' => '$.fields.Title',
				'type' => 'string',
			],
			'description' => [
				'name' => 'Description',
				'path' => '$.fields.Description',
				'type' => 'string',
			],
			'photo'       => [
				'name' => 'Photo URL',
				'path' => '$.fields.Photo[0].url',
				'type' => 'image_url',
			],
			'details_url' => [
				'name'     => 'Details URL',
				'generate' => function ( $data ) {
					return '/tulum-details/' . $data['id'];
				},
				'type'     => 'button_url',
			],
		],
	];

	return AirtableListItemsQuery::from_array([
		'data_source'   => $airtable_data_source,
		'input_schema'  => [],
		'output_schema' => $output_schema,
		'query_name'    => $config['tables'][0]['name'],
	]);
}

add_action( 'init', __NAMESPACE__ . '\\register_airtable_events_block' );
