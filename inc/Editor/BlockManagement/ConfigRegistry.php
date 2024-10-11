<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Editor\BlockManagement;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Config\QueryContext\QueryContextInterface;
use RemoteDataBlocks\Logging\LoggerManager;
use Psr\Log\LoggerInterface;
use RemoteDataBlocks\Editor\BlockPatterns\BlockPatterns;

use function get_page_by_path;
use function parse_blocks;
use function register_block_pattern;
use function serialize_blocks;
use function wp_insert_post;

class ConfigRegistry {
	private static LoggerInterface $logger;

	public static function init( ?LoggerInterface $logger = null ): void {
		self::$logger = $logger ?? LoggerManager::instance();
		ConfigStore::init( self::$logger );
	}

	public static function register_block( string $block_title, QueryContextInterface $display_query, array $options = [] ): void {
		$block_name = ConfigStore::get_block_name( $block_title );
		if ( ConfigStore::is_registered_block( $block_name ) ) {
			self::$logger->error( sprintf( 'Block %s has already been registered', $block_name ) );
			return;
		}

		// This becomes our target shape for a static config approach, but let's
		// give it some time to solidify.
		$config = [
			'description' => '',
			'name' => $block_name,
			'loop' => $options['loop'] ?? false,
			'patterns' => [],
			'queries' => [
				'__DISPLAY__' => $display_query,
			],
			'selectors' => [
				[
					'image_url' => $display_query->get_image_url(),
					'inputs' => array_map( function ( $slug, $input_var ) {
						return [
							'name' => $input_var['name'] ?? $slug,
							'required' => $input_var['required'] ?? true,
							'slug' => $slug,
							'type' => $input_var['type'] ?? 'string',
						];
					}, array_keys( $display_query->input_schema ), array_values( $display_query->input_schema ) ),
					'name' => 'Manual input',
					'query_key' => '__DISPLAY__',
					'type' => 'input',
				],
			],
			'title' => $block_title,
		];

		ConfigStore::set_configuration( $block_name, $config );
	}

	public static function register_loop_block( string $block_title, QueryContextInterface $display_query ): void {
		self::register_block( $block_title, $display_query, [ 'loop' => true ] );
	}

	public static function register_block_pattern( string $block_title, string $pattern_title, string $pattern_content, array $pattern_options = [] ): void {
		$block_name = ConfigStore::get_block_name( $block_title );
		$config = ConfigStore::get_configuration( $block_name );

		if ( null === $config ) {
			return;
		}

		// Add the block arg to any bindings present in the pattern.
		$parsed_blocks = parse_blocks( $pattern_content );
		$parsed_blocks = BlockPatterns::add_block_arg_to_bindings( $block_name, $parsed_blocks );
		$pattern_content = serialize_blocks( $parsed_blocks );
		$pattern_name = 'remote-data-blocks/' . sanitize_title( $pattern_title );

		// Create the pattern properties, allowing overrides via pattern options.
		$pattern_properties = array_merge(
			[
				'blockTypes' => [ $block_name ],
				'categories' => [ 'Remote Data' ],
				'content' => $pattern_content,
				'inserter' => true,
				'source' => 'plugin',
				'title' => $pattern_title,
			],
			$pattern_options['properties'] ?? []
		);

		// Register the pattern.
		register_block_pattern( $pattern_name, $pattern_properties );

		// If the pattern role is specified and recognized, add it to the block configuration.
		$recognized_roles = [ 'inner_blocks' ];
		if ( isset( $pattern_options['role'] ) && in_array( $pattern_options['role'], $recognized_roles, true ) ) {
			$config['patterns'][ $pattern_options['role'] ] = $pattern_name;
			ConfigStore::set_configuration( $block_name, $config );
		}
	}

	public static function register_page( string $block_title, string $page_slug ): void {
		$block_name = ConfigStore::get_block_name( $block_title );
		$config = ConfigStore::get_configuration( $block_name );

		if ( null === $config ) {
			return;
		}

		$display_query = $config['queries']['__DISPLAY__'];

		if ( empty( $display_query->input_schema ?? [] ) ) {
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
				'post_name' => $page_slug,
				'post_status' => 'draft',
				'post_title' => $block_title,
				'post_type' => 'page',
			] );
		}

		// Add a rewrite rule targeting the provided page slug.
		$query_var_pattern = '/([^/]+)';
		$query_vars = array_keys( $display_query->input_schema );
		$rewrite_rule = sprintf( '^%s%s/?$', $page_slug, str_repeat( $query_var_pattern, count( $query_vars ) ) );
		$rewrite_rule_target = sprintf( 'index.php?pagename=%s', $page_slug );

		foreach ( $query_vars as $index => $query_var ) {
			$rewrite_rule_target .= sprintf( '&%s=$matches[%d]', $query_var, $index + 1 );

			if ( ! isset( $display_query->input_schema[ $query_var ]['overrides'] ) ) {
				$display_query->input_schema[ $query_var ]['overrides'] = [];
			}

			// Add the URL variable override to the display query.
			$display_query->input_schema[ $query_var ]['overrides'][] = [
				'target' => $page_slug,
				'type' => 'url',
			];
		}

		add_rewrite_rule( $rewrite_rule, $rewrite_rule_target, 'top' );
	}

	private static function register_selector( string $block_title, string $type, QueryContextInterface $query ): void {
		$block_name = ConfigStore::get_block_name( $block_title );
		$config = ConfigStore::get_configuration( $block_name );
		$query_key = $query::class;

		if ( null === $config ) {
			return;
		}

		// Verify mappings.
		$to_query = $config['queries']['__DISPLAY__'];
		foreach ( array_keys( $to_query->input_schema ) as $to ) {
			if ( ! isset( $query->output_schema['mappings'][ $to ] ) ) {
				self::$logger->error( sprintf( 'Cannot map key "%s" from query "%s"', esc_html( $to ), $query_key ) );
				return;
			}
		}

		self::register_query( $block_title, $query );

		// Add the selector to the configuration. Fetch config again since it was
		// updated in register_query.
		$config = ConfigStore::get_configuration( $block_name );
		array_unshift(
			$config['selectors'],
			[
				'image_url' => $query->get_image_url(),
				'inputs' => [],
				'name' => $query->get_query_name(),
				'query_key' => $query_key,
				'type' => $type,
			]
		);

		ConfigStore::set_configuration( $block_name, $config );
	}

	public static function register_query( string $block_title, QueryContextInterface $query ): void {
		$block_name = ConfigStore::get_block_name( $block_title );
		$config = ConfigStore::get_configuration( $block_name );
		$query_key = $query::class;

		if ( null === $config ) {
			return;
		}

		if ( isset( $config['queries'][ $query_key ] ) ) {
			self::$logger->error( sprintf( 'Query %s has already been registered', $query_key ) );
			return;
		}

		$config['queries'][ $query_key ] = $query;
		ConfigStore::set_configuration( $block_name, $config );
	}

	public static function register_list_query( string $block_title, QueryContextInterface $query ): void {
		self::register_selector( $block_title, 'list', $query );
	}

	public static function register_search_query( string $block_title, QueryContextInterface $query ): void {
		if ( ! isset( $query->input_schema['search_terms'] ) ) {
			self::$logger->error( sprintf( 'A search query must have a "search_terms" input variable: %s', $query::class ) );
			return;
		}

		self::register_selector( $block_title, 'search', $query );
	}
}
