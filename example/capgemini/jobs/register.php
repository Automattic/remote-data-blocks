<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Capgemini\Jobs;

require_once __DIR__ . '/inc/class-capgemini-jobs-data-source.php';
require_once __DIR__ . '/inc/queries/class-capgemini-job-query.php';
require_once __DIR__ . '/inc/queries/class-capgemini-job-search-query.php';

function register_capgemini_job_search_block(): void {
	$block_name = 'Job Search';

	$capgemini_data_source = CapgeminiJobsDataSource::create();
	$capgemini_job_query = new CapgeminiJobQuery( $capgemini_data_source );
	$capgemini_job_search_query = new CapgeminiJobSearchQuery( $capgemini_data_source );

	register_remote_data_block( $block_name, $capgemini_job_search_query );
	register_remote_data_block( 'CapGemini Job', $capgemini_job_query );
}

add_action( 'init', __NAMESPACE__ . '\\register_capgemini_job_search_block' );
