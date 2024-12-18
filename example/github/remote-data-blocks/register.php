<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\GitHub;

use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Integrations\GitHub\GitHubDataSource;

require_once __DIR__ . '/github-query-runner.php';

function register_github_file_as_html_block(): void {
	$service_config = [
		'__version' => 1,
		'display_name' => 'Automattic/remote-data-blocks#trunk',
		'ref' => 'trunk',
		'repo_owner' => 'Automattic',
		'repo_name' => 'remote-data-blocks',
	];

	$block_title = sprintf( 'GitHub File As HTML (%s/%s)', $service_config['repo_owner'], $service_config['repo_name'] );
	$file_extension = '.md';
	$github_data_source = GitHubDataSource::from_array( [ 'service_config' => $service_config ] );

	$github_get_file_as_html_query = HttpQuery::from_array( [
		'data_source' => $github_data_source,
		'endpoint' => function ( array $input_variables ) use ( $service_config ): string {
			return sprintf(
				'https://api.github.com/repos/%s/%s/contents/%s?ref=%s',
				$service_config['repo_owner'],
				$service_config['repo_name'],
				$input_variables['file_path'],
				$service_config['ref']
			);
		},
		'input_schema' => [
			'file_path' => [
				'name' => 'File Path',
				'type' => 'string',
			],
		],
		'output_schema' => [
			'is_collection' => false,
			'type' => [
				'file_content' => [
					'name' => 'File Content',
					'generate' => [ GitHubQueryRunner::class, 'generate_file_content' ],
					'type' => 'html',
				],
				'file_path' => [
					'name' => 'File Path',
					'path' => '$.path',
					'type' => 'string',
				],
			],
		],
		'request_headers' => [
			'Accept' => 'application/vnd.github.html+json',
		],
		'query_runner' => new GitHubQueryRunner(),
	] );

	$github_get_list_files_query = HttpQuery::from_array( [
		'data_source' => $github_data_source,
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
	] );

	register_remote_data_block( [
		'title' => $block_title,
		'queries' => [
			'display' => $github_get_file_as_html_query,
			'list' => $github_get_list_files_query,
		],
		'query_input_overrides' => [
			[
				'query' => 'display',
				'source' => 'file_path',
				'source_type' => 'page',
				'target' => 'file_path',
				'target_type' => 'input_var',
			],
		],
		'pages' => [
			[
				'allow_nested_paths' => true,
				'slug' => 'gh',
				'title' => 'GitHub File',
			],
		],
		'patterns' => [
			[
				'html' => file_get_contents( __DIR__ . '/inc/patterns/file-render.html' ),
				'role' => 'inner_blocks',
				'title' => 'GitHub File Render',
			],
		],
	] );
}

add_action( 'init', __NAMESPACE__ . '\\register_github_file_as_html_block' );
