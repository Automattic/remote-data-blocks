<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\GitHub;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Integrations\GitHub\GitHubDataSource;
use RemoteDataBlocks\Logging\LoggerManager;

require_once __DIR__ . '/github-query-runner.php';

function register_github_file_as_html_block(): void {
	$service_config = [
		'repo_owner' => 'Automattic',
		'repo_name' => 'remote-data-blocks',
		'branch' => 'trunk',
	];

	$block_name = sprintf( 'GitHub File As HTML (%s/%s)', $service_config['repo_owner'], $service_config['repo_name'] );
	$file_extension = '.md';

	$github_data_source = GitHubDataSource::create(
		$service_config,
		[
			'request_headers' => [
				'Accept' => 'application/vnd.github.html+json',
			],
		],
	);

	$github_get_file_as_html_query = HttpQueryContext::from_array( [
		'data_source' => $github_data_source,
		'endpoint' => function ( array $input_variables ) use ( $service_config ): string {
			return sprintf(
				'https://api.github.com/repos/%s/%s/contents/%s?ref=%s',
				$service_config['repo_owner'],
				$service_config['repo_name'],
				$input_variables['file_path'],
				$service_config['branch']
			);
		},
		'input_schema' => [
			'file_path' => [
				'name' => 'File Path',
				'type' => 'string',
				'overrides' => [
					[
						'target' => 'utm_content',
						'type' => 'url',
					],
				],
			],
		],
		'output_schema' => [
			'is_collection' => false,
			'type' => [
				'file_content' => [
					'name' => 'File Content',
					'path' => '$.content',
					'type' => 'html',
				],
				'file_path' => [
					'name' => 'File Path',
					'path' => '$.path',
					'type' => 'string',
				],
			],
		],
		'query_name' => 'Get file as HTML',
		'query_runner' => new GitHubQueryRunner( $file_extension ),
	] );

	$github_get_list_files_query = HttpQueryContext::from_array( [
		'data_source' => $github_data_source,
		'endpoint' => function ( array $input_variables ) use ( $service_config ): string {
			return sprintf(
				'https://api.github.com/repos/%s/%s/contents/%s?ref=%s',
				$service_config['repo_owner'],
				$service_config['repo_name'],
				$input_variables['file_path'],
				$service_config['branch']
			);
		},
		'input_schema' => [
			'file_extension' => [
				'name' => 'File Extension',
				'type' => 'string',
			],
		],
		'output_schema' => [
			'is_collection' => true,
			'path' => sprintf( '$.tree[?(@.path =~ /\\.%s$/)]', ltrim( $file_extension, '.' ) ),
			'type' => [
				'file_path' => [
					'name' => 'File Path',
					'path' => '$.path',
					'type' => 'string',
				],
				'sha' => [
					'name' => 'SHA',
					'path' => '$.sha',
					'type' => 'string',
				],
				'size' => [
					'name' => 'Size',
					'path' => '$.size',
					'type' => 'integer',
				],
				'url' => [
					'name' => 'URL',
					'path' => '$.url',
					'type' => 'string',
				],
			],
		],
		'query_name' => 'List files',
		'query_runner' => new GitHubQueryRunner( $file_extension ),
	] );

	register_remote_data_block( $block_name, $github_get_file_as_html_query );
	register_remote_data_list_query( $block_name, $github_get_list_files_query );

	$block_pattern = file_get_contents( __DIR__ . '/inc/patterns/file-render.html' );
	register_remote_data_block_pattern( $block_name, 'GitHub File Render', $block_pattern, [
		'role' => 'inner_blocks',
	] );
	register_remote_data_page( $block_name, 'gh', [ 'allow_nested_paths' => true ] );

	$logger = LoggerManager::instance();
	$logger->info( sprintf( 'Registered %s block (branch: %s)', $block_name, $branch ) );
}

add_action( 'init', __NAMESPACE__ . '\\register_github_file_as_html_block' );
