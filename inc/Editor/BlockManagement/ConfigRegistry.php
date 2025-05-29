<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Editor\BlockManagement;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Logging\Logger;
use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Config\Query\QueryInterface;
use RemoteDataBlocks\Editor\BlockPatterns\BlockPatterns;
use RemoteDataBlocks\Logging\LoggerInterface;
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
	public const DISPLAY_QUERIES_KEY = 'display_queries';
	public const LIST_QUERY_KEY = 'list';
	public const SEARCH_QUERY_KEY = 'search';
	public const QUERIES_KEY = 'queries';

	public static function init( ?LoggerInterface $logger = null ): void {
		self::$logger = $logger ?? new Logger();
		ConfigStore::init( self::$logger );
	}

	private static function migrate_block_config( array $block_config = [] ): array|WP_Error {
		// Nothing to migrate, return the block config as is.
		if ( isset( $block_config[ self::QUERIES_KEY ] ) ) {
			return $block_config;
		}

		// if render_query is not set, error out.
		if ( ! isset( $block_config['render_query'] ) ) {
			return self::create_error( $block_config['title'], 'Render query is required' );
		}

		// Migrate from the old format, that conformed to the render_queries and selection_queries format, to the new single queries format.
		$queries = [];

		// Get the render query, inflate it, and set the type to display.
		$render_query = self::inflate_query( $block_config[ self::RENDER_QUERY_KEY ]['query'] );
		$queries[ self::DISPLAY_QUERY_KEY ] = $render_query;
		$block_config[ self::DISPLAY_QUERIES_KEY ] = [ self::DISPLAY_QUERY_KEY ];

		unset( $block_config[ self::RENDER_QUERY_KEY ] );

		if ( isset( $block_config[ self::SELECTION_QUERIES_KEY ] ) ) {
			// Get the selection queries, inflate them, and add them to the queries array using the type as the key.
			foreach ( $block_config[ self::SELECTION_QUERIES_KEY ] as $selection_query ) {
				$query = self::inflate_query( $selection_query['query'] );
				$queries[ $selection_query['type'] ] = $query;
			}

			unset( $block_config[ self::SELECTION_QUERIES_KEY ] );
		}

		// set the new keys.
		$block_config[ self::QUERIES_KEY ] = $queries;

		return $block_config;
	}

	public static function register_block( array $block_config = [] ): bool|WP_Error {
		// Migrate the block config to the new format.
		$block_config = self::migrate_block_config( $block_config );

		// Validate the provided user configuration.
		$schema = ConfigSchemas::get_remote_data_block_config_schema();
		$validator = new Validator( $schema, static::class, '$block_config' );
		$validated = $validator->validate( $block_config );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		// Check if the block has already been registered.
		$block_title = $block_config['title'];
		$block_name = ConfigStore::get_block_name( $block_title );
		if ( ConfigStore::is_registered_block( $block_name ) ) {
			return self::create_error( $block_title, sprintf( 'Block %s has already been registered', $block_name ) );
		}

		// Pre-validate the display queries, to ensure they exist.
		foreach ( $block_config['display_queries'] as $display_query_key ) {
			if ( ! isset( $block_config[ self::QUERIES_KEY ][ $display_query_key ] ) ) {
				return self::create_error( $block_title, sprintf( 'Display query "%s" not found', $display_query_key ) );
			}
		}

		$queries = [];
		$selectors = [];
		$display_queries_to_selectors_map = [];

		// Generate the selectors for the selector queries.
		foreach ( $block_config[ self::QUERIES_KEY ] as $query_key => $query ) {
			// Inflate the query.
			$query = self::inflate_query( $query );
			$input_schema = $query->get_input_schema();
			$output_schema = $query->get_output_schema();

			// This is only done because of ConfigStore::get_data_sources_as_array() needing it.
			$queries[ $query_key ] = $query;

			// Generate the selector for the display query, and then continue on to the next query.
			if ( in_array( $query_key, $block_config['display_queries'], true ) ) {
				$is_collection = true === ( $output_schema['is_collection'] ?? false );
				$has_required_variables = array_reduce(
					array_column( $input_schema, 'required' ),
					fn( $carry, $required ) => $carry || ( $required ?? true ),
					false
				);

				$selectors[] = [
					'display_name' => self::get_query_name_from_key( $query_key ),
					'image_url' => $query->get_image_url(),
					'inputs' => self::map_input_variables( $input_schema ),
					'name' => $has_required_variables ? 'Manual input' : ( $is_collection ? 'Load collection' : 'Load item' ),
					'query_key' => $query_key,
					'type' => $has_required_variables ? 'manual-input' : 'load-without-input',
				];

				$display_queries_to_selectors_map[ $query_key ][] = $query_key;

				continue;
			}

			foreach ( $block_config['display_queries'] as $display_query_key ) {
				$display_query = self::inflate_query( $block_config[ self::QUERIES_KEY ][ $display_query_key ] );
				$display_query_input_schema = $display_query->get_input_schema();

				// Check if the query's output schema intersects with the display query's input schema.
				$intersecting_keys = array_intersect_key( $output_schema['type'], $display_query_input_schema );

				// Skip this, if they don't intersect.
				if ( empty( $intersecting_keys ) ) {
					continue;
				}

				// Ensure the name and type of the schemas are truly valid.
				$valid_intersecting_keys = self::validate_selector_query_mapping( $intersecting_keys, $display_query_input_schema, $output_schema );
				if ( empty( $valid_intersecting_keys ) ) {
					continue;
				}

				// Validate the query mapping.
				$validation_result = self::validate_query_mapping( $display_query_input_schema, $input_schema, $output_schema, $block_title, $query_key );
				if ( is_wp_error( $validation_result ) ) {
					return $validation_result;
				}

				// Infer the type of the query.
				$inferred_type = self::infer_query_type( $input_schema, $output_schema );
				if ( is_wp_error( $inferred_type ) ) {
					return $inferred_type;
				}

				// Add the selector for the query.
				$selectors[] = [
					'display_name' => self::get_query_name_from_key( $query_key ),
					'image_url' => $query->get_image_url(),
					'inputs' => self::map_input_variables( $input_schema ),
					'name' => ucfirst( $query_key ),
					'query_key' => $query_key,
					'type' => $inferred_type,
				];

				$display_queries_to_selectors_map[ $display_query_key ][] = $query_key;

				// We have found the relevant display query, so we can break out of the loop.
				break;
			}
		}

		$config = [
			'description' => '',
			'icon' => $block_config['icon'] ?? 'cloud',
			'instructions' => $block_config['instructions'] ?? null,
			'name' => $block_name,
			'overrides' => $block_config['overrides'] ?? [],
			'patterns' => [],
			'queries' => $queries,
			'selectors' => $selectors,
			'display_queries_to_selectors' => $display_queries_to_selectors_map,
			'title' => $block_title,
		];

		// Register patterns which can be used with the block.
		foreach ( $block_config['patterns'] ?? [] as $pattern ) {
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

	private static function validate_selector_query_mapping( array $intersecting_keys, array $display_query_input_schema, array $output_schema ): array {
		return array_filter( $intersecting_keys, function ( $key ) use ( $display_query_input_schema, $output_schema ) {
			$display_query_fields = $display_query_input_schema[ $key ];

			// If the name doesn't match, skip.
			if ( $display_query_fields['name'] !== $output_schema['type'][ $key ]['name'] ) {
				return false;
			}

			// If the display query field is an id:list and the output schema field is an id, allow it as that's valid.
			if ( 'id:list' === $display_query_fields['type'] && 'id' === $output_schema['type'][ $key ]['type'] ) {
				return true;
			}

			// If the types don't match, skip.
			if ( $display_query_fields['type'] !== $output_schema['type'][ $key ]['type'] ) {
				return false;
			}

			// If the types match, allow it.
			return true;
		}, ARRAY_FILTER_USE_KEY );
	}

	private static function validate_query_mapping( array $to_query_input_schema, array $from_query_input_schema, array $from_query_output_schema, string $block_title, string $from_query_key ): WP_Error|bool {
		foreach ( array_keys( $to_query_input_schema ) as $to ) {
			if ( ! isset( $from_query_output_schema['type'][ $to ] ) ) {
				return self::create_error( $block_title, sprintf( 'Cannot map key "%1$s" from %2$s query. The display query for this block requires a "%1$s" key as an input, but it is not present in the output schema for the %2$s query. Try adding a "%1$s" mapping to the output schema for the %2$s query.', esc_html( $to ), $from_query_key ) );
			}
		}

		return true;
	}

	private static function register_block_pattern( string $block_name, string $pattern_title, string $pattern_content ): string {
		// Add the block arg to any bindings present in the pattern.
		$pattern_name = 'remote-data-blocks/' . sanitize_title_with_dashes( $pattern_title, '', 'save' );

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

	private static function map_input_variables( array $input_schema ): array {
		return array_map(
			function ( string $slug, array $input_var ): array {
				return [
					'default_value' => isset( $input_var['default_value'] ) ? strval( $input_var['default_value'] ) : null,
					'name' => $input_var['name'] ?? '',
					'slug' => $slug,
					'type' => $input_var['type'] ?? 'string',
					'required' => $input_var['required'] ?? false,
				];
			},
			array_keys( $input_schema ),
			array_values( $input_schema )
		);
	}

	private static function get_query_name_from_key( string $key ): string {
		// Replace any non-alphanumeric characters with spaces and convert to title case
		return ucwords( preg_replace( '/[^a-zA-Z0-9]/', ' ', $key ) );
	}

	private static function infer_query_type( array $input_schema, array $output_schema ): string|WP_Error {
		// If any input variable has type 'ui:search_input', it's a search query.
		foreach ( $input_schema as $input_var ) {
			if ( isset( $input_var['type'] ) && 'ui:search_input' === $input_var['type'] ) {
				return self::SEARCH_QUERY_KEY;
			}
		}

		// If output_schema has 'is_collection' true, it's a list query.
		if ( isset( $output_schema['is_collection'] ) && true === $output_schema['is_collection'] ) {
			return self::LIST_QUERY_KEY;
		}

		// This should never happen, but if it does, we need to error out.
		return self::create_error( 'Unknown query type', 'Could not infer the type of the query' );
	}
}
