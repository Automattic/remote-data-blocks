<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Capgemini\Jobs;

use RemoteDataBlocks\Editor\BlockManagement\ConfigRegistry;

require_once __DIR__ . '/inc/class-capgemini-jobs-data-source.php';
require_once __DIR__ . '/inc/queries/class-capgemini-job-query.php';
require_once __DIR__ . '/inc/queries/class-capgemini-job-filters-query.php';
require_once __DIR__ . '/inc/interactivity-store/interactivity-store.php';
require_once __DIR__ . '/inc/queries/class-capgemini-job-search-query.php';

function register_capgemini_job_search_blocks(): void {
	$block_name = 'Job Search Results';
	$capgemini_data_source = CapgeminiJobsDataSource::create();

	register_remote_data_block( 'CapGemini Job', new CapgeminiJobQuery( $capgemini_data_source ) );
	// register_remote_data_block( 'CapGemini Job Filters', new CapgeminiJobFiltersQuery( $capgemini_data_source ) );
	register_remote_data_loop_block( $block_name, new CapgeminiJobSearchQuery( $capgemini_data_source ) );
	register_remote_data_loop_block( 'Job Search Filters', new CapgeminiJobFiltersQuery( $capgemini_data_source ) );

	// Registering ad hoc queries is an unstable, undocumented feature.
	ConfigRegistry::register_query( $block_name, new CapgeminiJobFiltersQuery( $capgemini_data_source ) );

	register_block_type( __DIR__ . '/build/blocks/job-search-buttons' );
	register_block_type( __DIR__ . '/build/blocks/job-search-field' );
	register_block_type( __DIR__ . '/build/blocks/job-search-results' );
	register_block_type( __DIR__ . '/build/blocks/job-search-filters' );
}

add_action( 'init', __NAMESPACE__ . '\\register_capgemini_job_search_blocks' );
