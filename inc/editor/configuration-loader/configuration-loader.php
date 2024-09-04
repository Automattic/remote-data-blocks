<?php

namespace RemoteDataBlocks\Editor;

defined( 'ABSPATH' ) || exit();

use Error;
use RemoteDataBlocks\Config\QueryContext;
use RemoteDataBlocks\Config\ShopifyDatasource;
use RemoteDataBlocks\Config\ShopifyGetProductQuery;
use RemoteDataBlocks\Config\ShopifySearchProductsQuery;
use RemoteDataBlocks\Logging\Logger;
use RemoteDataBlocks\Logging\LoggerManager;
use RemoteDataBlocks\REST\DatasourceCRUD;

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

		add_action( 'init', [ __CLASS__, 'register_remote_data_blocks' ], 10, 0 );
	}

	public static function register_remote_data_blocks() {
		// Allow other plugins to register their blocks.
		do_action( 'register_remote_data_blocks' );
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
			'patterns'    => [],
			'queries'     => [
				'__DISPLAY__' => $display_query,
			],
			'selectors'   => [
				[
					'image_url' => $display_query->get_image_url(),
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
			'title'       => $block_title,
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

	private static function register_selector( string $block_title, string $type, QueryContext $query = null ): void {
		$block_name = self::get_block_name( $block_title );
		$config     = self::get_configuration( $block_name );
		$query_key  = $query::class;

		if ( null === $config ) {
			return;
		}

		// Verify mappings.
		if ( null !== $query ) {
			$to_query = $config['queries']['__DISPLAY__'];
			foreach ( array_keys( $to_query->input_variables ) as $to ) {
				if ( ! isset( $query->output_variables['mappings'][ $to ] ) ) {
					self::$logger->error( sprintf( 'Cannot map key "%s" from query "%s"', esc_html( $to ), $query_key ) );
					return;
				}
			}

			self::register_query( $block_title, $query );
		}

		array_unshift(
			self::$configurations[ $block_name ]['selectors'],
			[
				'image_url' => $query->get_image_url(),
				'inputs'    => [],
				'name'      => $query->get_query_name(),
				'query_key' => $query_key,
				'type'      => $type,
			]
		);
	}

	public static function register_query( string $block_title, QueryContext $query ): void {
		$block_name = self::get_block_name( $block_title );
		$config     = self::get_configuration( $block_name );
		$query_key  = $query::class;

		if ( null === $config ) {
			return;
		}

		if ( isset( $config['queries'][ $query_key ] ) ) {
			self::$logger->error( sprintf( 'Query %s has already been registered', $query_key ) );
			return;
		}

		self::$configurations[ $block_name ]['queries'][ $query_key ] = $query;
	}

	public static function register_list_query( string $block_title, QueryContext $query ): void {
		self::register_selector( $block_title, 'list', $query );
	}

	public static function register_search_query( string $block_title, QueryContext $query ): void {
		if ( ! isset( $query->input_variables['search_terms'] ) ) {
			self::$logger->error( sprintf( 'A search query must have a "search_terms" input variable: %s', $query::class ) );
			return;
		}

		self::register_selector( $block_title, 'search', $query );
	}

	public static function unregister_all(): void {
		if ( ! defined( 'REMOTE_DATA_BLOCKS__UNIT_TEST' ) || ! REMOTE_DATA_BLOCKS__UNIT_TEST ) {
			throw new Error( 'Unexpected unregistrations in config loader!' );
		}

		self::$configurations = [];
	}

	private static function register_blocks_for_dynamic_data_sources(): void {
		$data_sources_from_config = DatasourceCRUD::get_data_sources();

		foreach ( $data_sources_from_config as $_source ) {
			switch ( $_source->service ) {
				case 'shopify':
					$datasource = new ShopifyDatasource( $_source->token, $_source->store );
					$datasource->set_uuid( $_source->uuid );
					$datasource->set_slug( $_source->slug );
					self::register_blocks_for_shopify_data_source( $datasource );
					break;
				default:
					break;
			}
		}
	}

	private static function register_blocks_for_shopify_data_source( ShopifyDatasource $datasource ): void {
			$block_name = 'Shopify ' . $datasource->get_store_name();

			$shopify_get_product_query = new ShopifyGetProductQuery( $datasource );
			self::register_block( $block_name, $shopify_get_product_query );
			self::$logger->debug( sprintf( 'Registered "%s" block for dynamic data source - %s', $block_name, $datasource->get_slug() ) );

			$shopify_search_products_query = new ShopifySearchProductsQuery( $datasource );
			self::register_search_query( $block_name, $shopify_search_products_query );
			self::$logger->debug( sprintf( 'Registered "%s" _search query_ block for dynamic data source - %s', $block_name, $datasource->get_slug() ) );
	}
}
