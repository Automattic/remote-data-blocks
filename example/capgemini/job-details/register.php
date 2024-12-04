<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Capgemini\JobDetails;

use RemoteDataBlocks\Example\Capgemini\CapgeminiDataSource;
use RemoteDataBlocks\Example\Capgemini\CapgeminiJobDetailsQuery;
use RemoteDataBlocks\Example\Capgemini\CapgeminiJobListQuery;

require_once __DIR__ . '/../inc/class-capgemini-data-source.php';
require_once __DIR__ . '/../inc/queries/class-capgemini-job-details-query.php';
require_once __DIR__ . '/../inc/queries/class-capgemini-job-list-query.php';

function register_capgemini_job_details_block(): void {
    $block_name = 'Capgemini Job';
    
    $data_source = CapgeminiDataSource::from_array( [
        'slug' => 'capgemini-job-details',
        'service' => 'capgemini-job-details',
    ] );
    
    $get_job_details_query = new CapgeminiJobDetailsQuery( $data_source );
    $list_jobs_query = new CapgeminiJobListQuery( $data_source );

    register_remote_data_block( $block_name, $get_job_details_query );
    register_remote_data_list_query( $block_name, $list_jobs_query );
    register_remote_data_loop_block( 'Capgemini Jobs List', $list_jobs_query );
    register_remote_data_page( $block_name, 'job-details' );
}
add_action( 'init', __NAMESPACE__ . '\\register_capgemini_job_details_block' );
