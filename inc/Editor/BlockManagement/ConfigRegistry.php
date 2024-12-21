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

	public const DISPLAY_QUERY_KEY = 'display';
	public const LIST_QUERY_KEY = 'list';
	public const SEARCH_QUERY_KEY = 'search';

	public static function init( ?LoggerInterface $logger = null ): void {
		self::$logger = $logger ?? LoggerManager::instance();
		ConfigStore::init( self::$logger );
	}

	public static function register_block( array $user_config = [] ): bool|WP_Error {
		// Validate the provided user configuration.
		$schema = ConfigSchemas::get_remote_data_block_config_schema();
		$validator = new Validator( $schema, static::class );
		$validated = $validator->validate( $user_config );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		// Check if the block has already been registered.
		$block_title = $user_config['title'];
		$block_name = ConfigStore::get_block_name( $block_title );
		if ( ConfigStore::is_registered_block( $block_name ) ) {
			return self::create_error( $block_title, sprintf( 'Block %s has already been registered', $block_name ) );
		}

		$display_query = $user_config['queries'][ self::DISPLAY_QUERY_KEY ];
		$input_schema = $display_query->get_input_schema();

		// Build the base configuration for the block. This is our own internal
		// configuration, not what will be passed to WordPress's register_block_type.
		// @see BlockRegistration::register_block_type::register_blocks.
		$config = [
			'description' => '',
			'name' => $block_name,
			'loop' => $user_config['loop'] ?? false,
			'patterns' => [],
			'queries' => [
				self::DISPLAY_QUERY_KEY => $display_query,
			],
			'query_input_overrides' => [],
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
					'query_key' => self::DISPLAY_QUERY_KEY,
					'type' => 'input',
				],
			],
			'title' => $block_title,
		];

		// Register "selectors" which allow the user to use a query to assist in
		// selecting data for display by the block.
		foreach ( [ self::LIST_QUERY_KEY, self::SEARCH_QUERY_KEY ] as $from_query_type ) {
			if ( isset( $user_config['queries'][ $from_query_type ] ) ) {
				$to_query = $display_query;
				$from_query = $user_config['queries'][ $from_query_type ];

				$config['queries'][ $from_query_type ] = $from_query;

				$from_input_schema = $from_query->get_input_schema();
				$from_output_schema = $from_query->get_output_schema();

				foreach ( array_keys( $to_query->get_input_schema() ) as $to ) {
					if ( ! isset( $from_output_schema['type'][ $to ] ) ) {
						return self::create_error( $block_title, sprintf( 'Cannot map key "%s" from %s query', esc_html( $to ), $from_query_type ) );
					}
				}

				if ( self::SEARCH_QUERY_KEY === $from_query_type && ! isset( $from_input_schema['search_terms'] ) ) {
					return self::create_error( $block_title, 'A search query must have a "search_terms" input variable' );
				}

				// Add the selector to the configuration.
				array_unshift(
					$config['selectors'],
					[
						'image_url' => $from_query->get_image_url(),
						'inputs' => [],
						'name' => ucfirst( $from_query_type ),
						'query_key' => $from_query_type,
						'type' => $from_query_type,
					]
				);
			}
		}

		// Register query input overrides which allow the user to specify how
		// query inputs can be overridden by URL parameters or query variables.
		foreach ( $user_config['query_input_overrides'] ?? [] as $override ) {
			if ( ! isset( $config['queries'][ $override['query'] ] ) ) {
				return self::create_error( $block_title, sprintf( 'Query input override targets a non-existent query "%s"', esc_html( $override['query'] ) ) );
			}

			if ( 'input_var' !== $override['target_type'] ) {
				return self::create_error( $block_title, 'Only input variables can be targeted by query input overrides' );
			}

			if ( ! isset( $config['queries'][ $override['query'] ]->get_input_schema()[ $override['target'] ] ) ) {
				return self::create_error( $block_title, sprintf( 'Query input override "%s" does not exist as input variable for query "%s"', esc_html( $override['target'] ), esc_html( $override['query'] ) ) );
			}

			$config['query_input_overrides'][] = $override;
		}

		// Register patterns which can be used with the block.
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

		// Register pages assosciated with the block.
		foreach ( $user_config['pages'] ?? [] as $page_options ) {
			$registered = self::register_page( $config['query_input_overrides'], $block_title, $page_options );

			if ( is_wp_error( $registered ) ) {
				return self::create_error( $block_title, $registered->get_error_message() );
			}
		}

		ConfigStore::set_block_configuration( $block_name, $config );

		return true;
	}

	private static function register_block_pattern( string $block_name, string $pattern_title, string $pattern_content ): string {
		// Add the block arg to any bindings present in the pattern.
		$pattern_name = 'remote-data-blocks/' . sanitize_title_with_dashes( $pattern_title );

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
	 * Registers a page with optional configuration.
	 *
	 * @param array $query_input_overrides The query input overrides that will be targeted on the page.
	 * @param string $block_title The title of the block associated with the page.
	 * @param array {
	 *   allow_nested_paths?: bool
	 *   slug: string
	 *   title?: string
	 * } $options Configuration options for the page and rewrite rule.
	 */
	private static function register_page( array $query_input_overrides, string $block_title, array $options = [] ): bool|WP_Error {
		$overrides = array_values( array_filter( $query_input_overrides, function ( $override ) {
			return 'page' === $override['source_type'] && ConfigRegistry::DISPLAY_QUERY_KEY === $override['query'];
		} ) );

		if ( empty( $overrides ) ) {
			return new WP_Error( 'useless_page', 'A page is only useful with query input overrides with page sources.' );
		}

		$allow_nested_paths = $options['allow_nested_paths'] ?? false;
		$page_slug = $options['slug'];
		$page_title = $options['title'] ?? $block_title;

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
				'post_title' => $page_title,
				'post_type' => 'page',
			] );
		}

		// Add a rewrite rule targeting the provided page slug.
		$query_var_pattern = '/([^/]+)';

		/**
		 * If nested paths are allowed and there is only one query variable,
		 * allow slashes in the query variable value.
		 */
		if ( $allow_nested_paths && 1 === count( $overrides ) ) {
			$query_var_pattern = '/(.+)';
		}

		$rewrite_rule = sprintf( '^%s%s/?$', $page_slug, str_repeat( $query_var_pattern, count( $overrides ) ) );
		$rewrite_rule_target = sprintf( 'index.php?pagename=%s', $page_slug );

		foreach ( $overrides as $index => $override ) {
			$rewrite_rule_target .= sprintf( '&%s=$matches[%d]', $override['source'], $index + 1 );
		}

		add_rewrite_rule( $rewrite_rule, $rewrite_rule_target, 'top' );

		return true;
	}

	private static function create_error( string $block_title, string $message ): WP_Error {
		$error_message = sprintf( 'Error registering block %s: %s', esc_html( $block_title ), esc_html( $message ) );
		self::$logger->error( $error_message );
		return new WP_Error( 'block_registration_error', $error_message );
	}
}
