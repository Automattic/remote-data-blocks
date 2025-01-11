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
		'render_query' => [
			'query' => $github_get_file_as_html_query,
		],
		'selection_queries' => [
			[
				'query' => $github_get_list_files_query,
				'type' => 'list',
			],
		],
		'overrides' => [
			[
				'name' => 'github_file_path',
				'display_name' => __( 'Use GitHub file path from URL', 'rdb-example' ),
				'help_text' => __( 'Enable this override when using this block on the /gh/ page.', 'rdb-example' ),
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

	// A page with slug "gh" must be created.
	add_rewrite_rule( '^gh/(.+)/?', 'index.php?pagename=gh&file_path=$matches[1]', 'top' );

	add_filter( 'query_vars', function ( array $query_vars ): array {
		$query_vars[] = 'file_path';
		return $query_vars;
	}, 10, 1 );

	add_filter( 'remote_data_blocks_query_input_variables', function ( array $input_variables, array $enabled_overrides ): array {
		if ( true === in_array( 'github_file_path', $enabled_overrides, true ) ) {
			$file_path = get_query_var( 'file_path' );

			if ( ! empty( $file_path ) ) {
				$input_variables['file_path'] = $file_path;
			}
		}

		return $input_variables;
	}, 10, 2 );
}

add_action( 'init', __NAMESPACE__ . '\\register_github_file_as_html_block' );
