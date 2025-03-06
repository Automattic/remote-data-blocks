<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Editor\BlockManagement;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Logging\LoggerManager;
use Psr\Log\LoggerInterface;
use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Config\Query\QueryInterface;
use RemoteDataBlocks\Editor\BlockPatterns\BlockPatterns;
use RemoteDataBlocks\Validation\ConfigSchemas;
use RemoteDataBlocks\Validation\Validator;
use WP_Error;

use function parse_blocks;
use function register_block_pattern;
use function serialize_blocks;

class ConfigRegistry {
	private static LoggerInterface $logger;

	public const RENDER_QUERY_KEY = 'render_query';
	public const SELECTION_QUERIES_KEY = 'selection_queries';
	public const DISPLAY_QUERY_KEY = 'display';
	public const LIST_QUERY_KEY = 'list';
	public const SEARCH_QUERY_KEY = 'search';
	public const COLLECTION_QUERY_KEY = 'collection';

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

		$display_query = self::inflate_query( $user_config[ self::RENDER_QUERY_KEY ]['query'] );
		$input_schema = $display_query->get_input_schema();

		// Check if render query has any bulk-supporting inputs
		$bulk_supported_inputs = array_filter(
			$input_schema,
			function ( $input ) {
				return $input['supports_bulk'] ?? false;
			}
		);

		if ( count( $bulk_supported_inputs ) > 0 ) {
			// Ensure only one input variable when bulk selection is enabled
			if ( count( $input_schema ) > 1 ) {
				return self::create_error(
					$block_title,
					'Render queries with bulk selection can only have one input variable'
				);
			}
		}

		$has_bulk_support = !empty( $bulk_supported_inputs );

			// Initialize queries array with display query as default
			$queries = [
				self::DISPLAY_QUERY_KEY => $display_query,
			];
	
			// Process additional render queries if present
			if ( isset( $user_config[ self::RENDER_QUERY_KEY ]['queries'] ) && is_array( $user_config[ self::RENDER_QUERY_KEY ]['queries'] ) ) {
				foreach ( $user_config[ self::RENDER_QUERY_KEY ]['queries'] as $query_key => $query_config ) {
					$queries[ $query_key ] = self::inflate_query( $query_config['query'] );
				}
			}
	
			// Build the base configuration for the block. This is our own internal
			// configuration, not what will be passed to WordPress's register_block_type.
			// @see BlockRegistration::register_block_type::register_blocks.
			$config = [
				'description' => '',
				'name' => $block_name,
				'loop' => $user_config[ self::RENDER_QUERY_KEY ]['loop'] ?? false,
				'overrides' => $user_config['overrides'] ?? [],
				'patterns' => [],
				'queries' => $queries,
				'selectors' => [
					self::create_selector( $display_query, self::DISPLAY_QUERY_KEY, 'input', 'Manual input', $has_bulk_support ),
				],
				'title' => $block_title,
			];

			// Add collection queries to selectors if present
			if ( isset( $user_config[ self::RENDER_QUERY_KEY ]['queries'] ) && is_array( $user_config[ self::RENDER_QUERY_KEY ]['queries'] ) ) {
				foreach ( $user_config[ self::RENDER_QUERY_KEY ]['queries'] as $query_key => $query_config ) {
					array_unshift(
						$config['selectors'],
						self::create_selector(
							$queries[ $query_key ],
							$query_key,
							$query_config['type'],
							$query_config['display_name'] ?? null,
							$query_config['supports_bulk'] ?? false
						)
					);
				}
			}

			// Register "selectors" which allow the user to use a query to assist in
			// selecting data for display by the block.
			foreach ( $user_config[ self::SELECTION_QUERIES_KEY ] ?? [] as $selection_query ) {
				$from_query = self::inflate_query( $selection_query['query'] );
				$from_query_type = $selection_query['type'];
				$to_query = $display_query;

				$config['queries'][ $from_query::class ] = $from_query;

				$from_input_schema = $from_query->get_input_schema();
				$from_output_schema = $from_query->get_output_schema();

				foreach ( array_keys( $to_query->get_input_schema() ) as $to ) {
					if ( ! isset( $from_output_schema['type'][ $to ] ) ) {
						return self::create_error( $block_title, sprintf( 'Cannot map key "%1$s" from %2$s query. The display query for this block requires a "%1$s" key as an input, but it is not present in the output schema for the %2$s query. Try adding a "%1$s" mapping to the output schema for the %2$s query.', esc_html( $to ), $from_query_type ) );
					}
				}

				if ( self::SEARCH_QUERY_KEY === $from_query_type ) {
					$search_input_count = count( array_filter( $from_input_schema, function ( array $input_var ): bool {
						return 'ui:search_input' === $input_var['type'];
					} ) );

					if ( 1 !== $search_input_count ) {
						return self::create_error( $block_title, 'A search query must have one input variable with type "ui:search_input"' );
					}
				}

				// Add the selector to the configuration.
				array_unshift(
					$config['selectors'],
					self::create_selector(
						$from_query,
						$from_query::class,
						$from_query_type,
						$selection_query['display_name'] ?? null,
						$has_bulk_support
					)
				);
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

	private static function create_error( string $block_title, string $message ): WP_Error {
		$error_message = sprintf( 'Error registering block %s: %s', esc_html( $block_title ), esc_html( $message ) );
		self::$logger->error( $error_message );
		return new WP_Error( 'block_registration_error', $error_message );
	}

	private static function inflate_query( array|QueryInterface $config ): QueryInterface {
		if ( is_array( $config ) ) {
			return HttpQuery::from_array( $config );
		}

		return $config;
	}

	// Create a selector for a query
	private static function create_selector(
		QueryInterface $query,
		string $query_key,
		string $type,
		?string $display_name = null,
		bool $supports_bulk = false
	): array {
		return [
			'image_url' => $query->get_image_url(),
			'inputs' => array_map( function ( $slug, $input_var ) {
				return [
					'name' => $input_var['name'] ?? $slug,
					'required' => $input_var['required'] ?? false,
					'slug' => $slug,
					'type' => $input_var['type'] ?? 'string',
					'supports_bulk' => $input_var['supports_bulk'] ?? false,
				];
			}, array_keys( $query->get_input_schema() ), array_values( $query->get_input_schema() ) ),
			'name' => $display_name ?? ucfirst( $type ),
			'query_key' => $query_key,
			'type' => $type,
			'supports_bulk' => $supports_bulk,
		];
	}
}
