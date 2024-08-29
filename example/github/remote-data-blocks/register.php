<?php

namespace RemoteDataBlocks\Example\GitHub;

use RemoteDataBlocks\Editor\ConfigurationLoader;
use RemoteDataBlocks\Logging\LoggerManager;
use function add_action;

require_once __DIR__ . '/inc/queries/class-github-datasource.php';
require_once __DIR__ . '/inc/queries/class-github-get-list-files-query.php';
require_once __DIR__ . '/inc/queries/class-github-get-raw-file-query.php';

function register_github_file_block() {
	$repo_owner  = 'Automattic';
	$repo_name   = 'remote-data-blocks';
	$branch      = 'trunk';

	$block_name  = sprintf( 'GitHub File (%s/%s)', $repo_owner, $repo_name );

	$github_datasource             = new GitHubDatasource( $repo_owner, $repo_name, $branch );
	$github_get_list_files_query   = new GitHubListFilesQuery( $github_datasource );
	$github_get_raw_file_query     = new GitHubGetRawFileQuery( $github_datasource );

    ConfigurationLoader::register_block( $block_name, $github_get_raw_file_query );
	ConfigurationLoader::register_list_query( $block_name, $github_get_list_files_query );
	// ConfigurationLoader::register_page( $block_name, 'github-remote-data-blocks-embed-file' );

	$block_pattern = file_get_contents( __DIR__ . '/inc/patterns/file-picker.html' );
	ConfigurationLoader::register_block_pattern( $block_name, 'remote-data-blocks/github-file-picker', $block_pattern, [ 'title' => 'GitHub File Picker' ] );

	$logger = LoggerManager::instance();
	$logger->info( sprintf( 'Registered %s block (branch: %s)', $block_name, $branch ) );
}
add_action( 'register_remote_data_blocks', __NAMESPACE__ . '\\register_github_file_block' );
