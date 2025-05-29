<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\GitHub;

use RemoteDataBlocks\Integrations\GitHub\GitHubDataSource;

require_once __DIR__ . '/inc/github-query-runner.php';
require_once __DIR__ . '/inc/markdown-links.php';

/**
 * Registers a remote data block for rendering Markdown files from a public
 * GitHub repository.
 *
 * HttpQuery expects APIs to return JSON, but we instruct GitHub's API to return
 * HTML (converted from Markdown). To handle this, we provide a custom query
 * runner to hangle the HTML response and update Markdown links.
 *
 * @docs /docs/extending/query-runner.md
 */
function register_github_markdown_remote_data_block(): void {
	$repo_owner = 'Automattic';
	$repo_name = 'remote-data-blocks';
	$repo_ref = 'trunk';

	// Note: This repository is public, so GitHub's API does not require authorization.
	$github_data_source = [
		// Target a subclass of HttpDataSource for instantiation.
		'__class' => GitHubDataSource::class,
		'service_config' => [
			'__version' => 1,
			'display_name' => 'GitHub Markdown (Remote Data Blocks)',
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'ref' => $repo_ref,
		],
	];

	$block_title = sprintf( 'GitHub Markdown File (%s/%s)', $repo_owner, $repo_name );
	$file_extension = '.md';

	$get_file_as_html_query = [
		'data_source' => $github_data_source,
		// Provide a callable (closure) to dynamically generate the endpoint using
		// variables in the outer scope and the input variables.
		'endpoint' => function ( array $input_variables ) use ( $repo_owner, $repo_name, $repo_ref ): string {
			return sprintf(
				'https://api.github.com/repos/%s/%s/contents/%s?ref=%s',
				$repo_owner,
				$repo_name,
				$input_variables['file_path'],
				$repo_ref
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
					// Instead of a `path`, we provide a `generate` function to create the
					// image URL.
					'generate' => static function ( array $data ): string {
						// Update the markdown links so that they point to the correct location.
						return update_markdown_links( $data['content'], $data['path'] );
					},
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
			// This request header instructs GitHub's API to convert the Markdown to HTML.
			'Accept' => 'application/vnd.github.html+json',
		],
		// A custom query runner allows us to work with raw HTML responses (instead of JSON).
		'query_runner' => new GitHubQueryRunner(),
	];

	$get_list_files_query = [
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
	];

	register_remote_data_block( [
		'title' => $block_title,
		'render_query' => [
			'query' => $get_file_as_html_query,
		],
		'selection_queries' => [
			[
				'query' => $get_list_files_query,
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
				'role' => 'inner_blocks', // Bypass the pattern selection step.
				'title' => 'GitHub File Render',
			],
		],
	] );
}
add_action( 'init', __NAMESPACE__ . '\\register_github_markdown_remote_data_block' );

/**
 * Adds a rewrite rule and filters the query vars to create a dynamic page that
 * will display the requested markdown file from the GitHub repository.
 */
function handle_github_file_path_override(): void {
	// This rewrite targets a page with the slug "gh", which must be created!
	add_rewrite_rule( '^gh/(.+)/?', 'index.php?pagename=gh&file_path=$matches[1]', 'top' );

	// Add the "file_path" query variable to the list of recognized query variables.
	add_filter( 'query_vars', function ( array $query_vars ): array {
		$query_vars[] = 'file_path';
		return $query_vars;
	}, 10, 1 );

	// Filter the query input variables to inject the "file_path" value from the
	// URL. Note that the override must match the override name defined in the
	// block registration above.
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
add_action( 'init', __NAMESPACE__ . '\\handle_github_file_path_override' );
