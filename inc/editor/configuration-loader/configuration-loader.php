<?php

namespace RemoteDataBlocks\Editor;

defined( 'ABSPATH' ) || exit();

use Error;
use RemoteDataBlocks\Config\QueryContext;
use RemoteDataBlocks\Logging\Logger;
use RemoteDataBlocks\Logging\LoggerManager;

use function add_action;
use function do_action;
use function get_page_by_path;
use function sanitize_title;
use function wp_insert_post;

class ConfigurationLoader {
	private static array $configurations = [];
	private static Logger $logger;

	public static function init() {
		self::$logger = LoggerManager::instance();

		add_action( 'init', [ __CLASS__, 'register_remote_blocks' ], 10, 0 );
	}

	public static function register_remote_blocks() {
		// Allow other plugins to register their blocks.
		do_action( 'register_remote_blocks' );
	}

	/**
	 * Convert a block title to a block name. Mainly this is to reduce the burden
	 * of configuration and to ensure that block names are unique (since block
	 * titles must be unique).
	 */
	public static function get_block_name( string $block_title ): string {
		return 'remote-data-blocks/' . sanitize_title( $block_title );
	}

	public static function get_block_names(): array {
		return array_keys( self::$configurations );
	}

	public static function get_configuration( string $block_name ): array|null {
		if ( ! isset( self::$configurations[ $block_name ] ) ) {
			self::$logger->error( sprintf( 'Block %s has not been registered', $block_name ) );
			return null;
		}

		return self::$configurations[ $block_name ];
	}

	public static function is_registered_block( string $block_name ): bool {
		return isset( self::$configurations[ $block_name ] );
	}

	public static function register_block( string $block_title, QueryContext $display_query ): void {
		$block_name = self::get_block_name( $block_title );
		if ( isset( self::$configurations[ $block_name ] ) ) {
			self::$logger->error( sprintf( 'Block %s has already been registered', $block_name ) );
			return;
		}

		// This becomes our target shape for a static config approach, but let's
		// give it some time to solidify.
		self::$configurations[ $block_name ] = [
			'description' => '',
			'name'        => $block_name,
			'loop'        => false,
			'panels'      => [
				[
					'inputs'    => array_map( function ( $slug, $input_var ) {
						return [
							'name'     => $input_var['name'] ?? $slug,
							'required' => $input_var['required'] ?? true,
							'slug'     => $slug,
							'type'     => $input_var['type'] ?? 'string',
						];
					}, array_keys( $display_query->input_variables ), array_values( $display_query->input_variables ) ),
					'name'      => 'Manual input',
					'query_key' => '__DISPLAY__',
					'type'      => 'input',
				],
			],
			'patterns'    => [],
			'queries'     => [
				'__DISPLAY__' => $display_query,
			],
			'title'       => $block_title,
		];

		self::$configurations[ $block_name ]['panels'][] = [
			'name'      => '',
			'query_key' => '__DISPLAY__',
			'type'      => '',
		];
	}

	public static function register_loop_block( string $block_title, QueryContext $display_query, array $options = [] ): void {
		$block_name = self::get_block_name( $block_title );
		self::register_block( $block_title, $display_query, array_merge( $options, [ 'loop' => true ] ) );
		self::$configurations[ $block_name ]['loop'] = true;
	}

	public static function register_block_pattern( string $block_title, string $pattern_name, string $pattern_content, array $pattern_options = [] ): void {
		$block_name = self::get_block_name( $block_title );
		$config     = self::get_configuration( $block_name );

		if ( null === $config ) {
			return;
		}

		self::$configurations[ $block_name ]['patterns'][ $pattern_name ] = array_merge(
			[
				'blockTypes' => [ $block_name ],
				'categories' => [ 'Remote Data' ],
				'content'    => $pattern_content,
				'inserter'   => true,
				'source'     => 'plugin',
				'title'      => $pattern_name,
			],
			$pattern_options
		);
	}

	public static function register_page( string $block_title, string $page_slug ): void {
		$block_name = self::get_block_name( $block_title );
		$config     = self::get_configuration( $block_name );

		if ( null === $config ) {
			return;
		}

		$display_query = $config['queries']['__DISPLAY__'];

		if ( empty( $display_query->input_variables ?? [] ) ) {
			self::$logger->error( 'A page is only useful for queries with input variables.' );
			return;
		}

		// Create the page if it doesn't already exist.
		if ( null === get_page_by_path( '/' . $page_slug ) ) {
			$post_content = sprintf(
				"<!-- wp:paragraph -->\n<p>Add a %s block and use the “Remote data overrides” panel to allow URL parameters to override the selected data.</p>\n<!-- /wp:paragraph -->",
				$block_title
			);

			wp_insert_post( [
				'post_content' => $post_content,
				'post_name'    => $page_slug,
				'post_status'  => 'draft',
				'post_title'   => $block_title,
				'post_type'    => 'page',
			] );
		}

		// Add a rewrite rule targeting the provided page slug.
		$query_var_pattern   = '/([^/]+)';
		$query_vars          = array_keys( $display_query->input_variables );
		$rewrite_rule        = sprintf( '^%s%s/?$', $page_slug, str_repeat( $query_var_pattern, count( $query_vars ) ) );
		$rewrite_rule_target = sprintf( 'index.php?pagename=%s', $page_slug );

		foreach ( $query_vars as $index => $query_var ) {
			$rewrite_rule_target .= sprintf( '&%s=$matches[%d]', $query_var, $index + 1 );

			if ( ! isset( $display_query->input_variables[ $query_var ]['overrides'] ) ) {
				$display_query->input_variables[ $query_var ]['overrides'] = [];
			}

			// Add the URL variable override to the display query.
			$display_query->input_variables[ $query_var ]['overrides'][] = [
				'target' => $page_slug,
				'type'   => 'url',
			];
		}

		add_rewrite_rule( $rewrite_rule, $rewrite_rule_target, 'top' );
	}

	private static function register_panel( string $block_title, string $panel_name, string $panel_type, QueryContext $query = null ): void {
		$block_name = self::get_block_name( $block_title );
		$config     = self::get_configuration( $block_name );

		if ( null === $config ) {
			return;
		}

		// Generate a query key for this panel, unique to the block. We could create
		// persistent IDs more formally, but this is a simple method that doesn't
		// put too much burden on the user.
		$query_key = sprintf( '__PANEL__%s', sanitize_title( $panel_name ) );
		if ( isset( $config['queries'][ $query_key ] ) ) {
			self::$logger->error( sprintf( 'Panel %s has already been registered', $panel_name ) );
			return;
		}

		switch ( $panel_type ) {
			case 'list':
				break;

			case 'search':
				if ( ! isset( $query->input_variables['search_terms'] ) ) {
					self::$logger->error( 'Search panel query must have a "search_terms" input variable' );
					return;
				}

				break;

			default:
				self::$logger->error( 'Invalid panel type' );
				return;
		}

		array_unshift(
			self::$configurations[ $block_name ]['panels'],
			[
				'inputs'    => [],
				'name'      => $panel_name,
				'query_key' => $query_key,
				'type'      => $panel_type,
			]
		);

		// Verify mappings.
		if ( null !== $query ) {
			$to_query = $config['queries']['__DISPLAY__'];
			foreach ( array_keys( $to_query->input_variables ) as $to ) {
				if ( ! isset( $query->output_variables['mappings'][ $to ] ) ) {
					self::$logger->error( sprintf( 'Cannot map key "%s" from query "%s"', esc_html( $to ), $query::class ) );
					return;
				}
			}
			self::$configurations[ $block_name ]['queries'][ $query_key ] = $query;
		}
	}

	public static function register_list_panel( string $block_title, string $panel_name, QueryContext $query ): void {
		self::register_panel( $block_title, $panel_name, 'list', $query );
	}

	public static function register_query( string $block_title, string $query_name, QueryContext $query ): void {
		$block_name = self::get_block_name( $block_title );
		$config     = self::get_configuration( $block_name );

		if ( null === $config ) {
			return;
		}

		self::$configurations[ $block_name ]['queries'][ $query_name ] = $query;
	}

	public static function register_search_panel( string $block_title, string $panel_name, QueryContext $query ): void {
		self::register_panel( $block_title, $panel_name, 'search', $query );
	}

	public static function unregister_all(): void {
		if ( ! defined( 'REMOTE_DATA_BLOCKS__UNIT_TEST' ) || ! REMOTE_DATA_BLOCKS__UNIT_TEST ) {
			throw new Error( 'Unexpected unregistrations in config loader!' );
		}

		self::$configurations = [];
	}
}
