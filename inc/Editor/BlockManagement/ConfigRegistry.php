<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Editor\BlockManagement;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Logging\LoggerManager;
use Psr\Log\LoggerInterface;
use RemoteDataBlocks\Editor\BlockPatterns\BlockPatterns;
use RemoteDataBlocks\Validation\ConfigSchemas;
use RemoteDataBlocks\Validation\Validator;
use WP_Error;

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

	public static function register_block( array $user_config = [] ): true|WP_Error {
		$schema = ConfigSchemas::get_remote_data_block_config_schema();
		$validator = new Validator( $schema, static::class );
		$validated = $validator->validate( $user_config );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$block_title = $user_config['title'];
		$block_name = ConfigStore::get_block_name( $block_title );
		if ( ConfigStore::is_registered_block( $block_name ) ) {
			$error_message = sprintf( 'Block %s has already been registered', $block_name );
			self::$logger->error( $error_message );
			return new WP_Error( 'block_already_registered', $error_message );
		}

		$display_query = $user_config['queries']['display'];
		$input_schema = $display_query->get_input_schema();
		$output_schema = $display_query->get_output_schema();

		$config = [
			'description' => '',
			'name' => $block_name,
			'loop' => $output_schema['is_collection'] ?? false,
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
					}, array_keys( $input_schema ), array_values( $input_schema ) ),
					'name' => 'Manual input',
					'query_key' => '__DISPLAY__',
					'type' => 'input',
				],
			],
			'title' => $block_title,
		];

		// Register "selectors" which allow the user to use a query to assist in
		// selecting data for display by the block.
		foreach ( [ 'list', 'search' ] as $from_query_type ) {
			if ( isset( $user_config['queries'][ $from_query_type ] ) ) {
				$to_query = $display_query;
				$from_query = $user_config['queries'][ $from_query_type ];
				$from_query_key = $from_query->get_query_key();

				$config['queries'][ $from_query_key ] = $from_query;

				$input_schema = $from_query->get_input_schema();
				$output_schema = $from_query->get_output_schema();

				foreach ( array_keys( $to_query->get_input_schema() ) as $to ) {
					if ( ! isset( $output_schema['type'][ $to ] ) ) {
						$error_message = sprintf( 'Cannot map key "%s" from query "%s"', esc_html( $to ), $from_query_key );
						self::$logger->error( $error_message );
						return new WP_Error( 'invalid_query_mapping', $error_message );
					}
				}

				if ( 'search' === $from_query_type && ! isset( $input_schema['search_terms'] ) ) {
					$error_message = sprintf( 'A search query must have a "search_terms" input variable: %s', $from_query_key );
					self::$logger->error( $error_message );
					return new WP_Error( 'invalid_query_mapping', $error_message );
				}

				// Add the selector to the configuration.
				array_unshift(
					$config['selectors'],
					[
						'image_url' => $from_query->get_image_url(),
						'inputs' => [],
						'name' => $from_query->get_query_name(),
						'query_key' => $from_query_key,
						'type' => $from_query_type,
					]
				);
			}
		}

		foreach ( $user_config['patterns'] ?? [] as $pattern ) {
			$parsed_blocks = parse_blocks( $pattern['html'] );
			$parsed_blocks = BlockPatterns::add_block_arg_to_bindings( $block_name, $parsed_blocks );
			$pattern_content = serialize_blocks( $parsed_blocks );

			$pattern_name = self::register_block_pattern( $block_name, $pattern['title'], $pattern_content );

			// If the pattern role is specified and recognized, add it to the block configuration.
			$recognized_roles = [ 'inner_blocks' ];
			if ( isset( $pattern['role'] ) && in_array( $pattern['role'], $recognized_roles, true ) ) {
				$config['patterns'][ $pattern['role'] ] = $pattern_name;
			}
		}

		ConfigStore::set_block_configuration( $block_name, $config );

		return true;
	}

	private static function register_block_pattern( string $block_name, string $pattern_title, string $pattern_content ): string {
		// Add the block arg to any bindings present in the pattern.
		$pattern_name = 'remote-data-blocks/' . sanitize_title( $pattern_title );

		// Create the pattern properties, allowing overrides via pattern options.
		$pattern_properties = [
			'blockTypes' => [ $block_name ],
			'categories' => [ 'Remote Data' ],
			'content' => $pattern_content,
			'inserter' => true,
			'source' => 'plugin',
			'title' => $pattern_title,
		];

		// Register the pattern.
		register_block_pattern( $pattern_name, $pattern_properties );

		return $pattern_name;
	}

	/**
	 * Registers a page query with optional configuration.
	 *
	 * @param string       $block_title   The block title.
	 * @param string       $page_slug     The page slug.
	 * @param array {
	 *     allow_nested_paths?: bool
	 * }                   $options       Optional. Configuration options for the rewrite rule.
	 */
	public static function register_page( string $block_title, string $page_slug, array $options = [] ): void {

		$block_name = ConfigStore::get_block_name( $block_title );
		$config = ConfigStore::get_block_configuration( $block_name );
		$allow_nested_paths = $options['allow_nested_paths'] ?? false;

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
		$query_vars = array_keys( $display_query->input_schema );

		$query_var_pattern = '/([^/]+)';

		/**
		 * If nested paths are allowed and there is only one query variable,
		 * allow slashes in the query variable value.
		 */
		if ( $allow_nested_paths && 1 === count( $query_vars ) ) {
			$query_var_pattern = '/(.+)';
		}

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
}
