<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\CapGemeni\Jobs;

require_once __DIR__ . '/inc/class-capgemeni-jobs-data-source.php';
require_once __DIR__ . '/inc/queries/class-capgemeni-job-search-query.php';

function register_jobs_search_results_block() {
	$loop_block_name = 'Job Search Results';

	$capgemeni_data_source = AirtableDataSource::create( $access_token, 'appVQ2PAl95wQSo9S', [], 'Conference Events' );
	// $airtable_get_event_query = new AirtableGetEventQuery( $airtable_data_source );
	$capgemeni_job_search_query = new CapGemeniJobSearchQuery( $capgemeni_data_source );

	// register_remote_data_block( $block_name, $airtable_get_event_query );
	register_remote_data_list_query( $loop_block_name, $capgemeni_job_search_query );
	register_remote_data_loop_block( 'Job Search Results', $capgemeni_job_search_query );
	// register_remote_data_page( $block_name, 'conference-event' );
}

add_action( 'init', __NAMESPACE__ . '\\register_airtable_events_block' );
