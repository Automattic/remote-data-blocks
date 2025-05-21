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
	public const LIST_QUERY_KEY = 'list';
	public const SEARCH_QUERY_KEY = 'search';
	public const QUERIES_KEY = 'queries';

	public static function init( ?LoggerInterface $logger = null ): void {
		self::$logger = $logger ?? new Logger();
		ConfigStore::init( self::$logger );
	}

	private static function migrate_block_config( array $block_config = [] ): array|WP_Error {
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
		$render_query->set_type( self::DISPLAY_QUERY_KEY );
		$queries[ self::DISPLAY_QUERY_KEY ] = $render_query;

		unset( $block_config[ self::RENDER_QUERY_KEY ] );

		if ( isset( $block_config[ self::SELECTION_QUERIES_KEY ] ) ) {
			// Get the selection queries, inflate them, add them to the required_query field on the render query and correctly set the type based on the type field in the selection query.
			foreach ( $block_config[ self::SELECTION_QUERIES_KEY ] as $selection_query ) {
				$query = self::inflate_query( $selection_query['query'] );
				$query->set_type( $selection_query['type'] );
				$render_query->set_required_query( $selection_query['type'] );
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
		// Note: This will not handle the case where the required query is not present in the queries array.
		// That has to be done manually for now.
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

		$queries = [];
		$selectors = [];

		// This ensures we process everything in one pass, and that we don't process the same query twice.
		foreach ( $block_config[ self::QUERIES_KEY ] as $query_key => $query ) {
			// Skip if its already in queries.
			if ( isset( $queries[ $query_key ] ) ) {
				continue;
			}

			// Inflate the query, so it's an HttpQuery and add it to the queries array.
			$query = self::inflate_query( $query );
			$queries[ $query_key ] = $query;
			$input_schema = $query->get_input_schema();
			$output_schema = $query->get_output_schema();

			// This is a 1:1 mapping at the moment, between the required query and the display query that requires it.
			if ( $query->get_required_query() && ! empty( $query->get_required_query() ) ) {

				// Ensure the required query exists.
				if ( ! isset( $block_config[ self::QUERIES_KEY ][ $query->get_required_query() ] ) ) {
					return self::create_error( $block_title, sprintf( 'Required query "%s" not found', $query->get_required_query() ) );
				}

				// Inflate the required query, so it's an HttpQuery.
				$required_query = self::inflate_query( $block_config[ self::QUERIES_KEY ][ $query->get_required_query() ] );

				$required_query_key = $query->get_required_query();
				$required_query_type = $required_query->get_type();
				$required_query_input_schema = $required_query->get_input_schema();
				$required_query_output_schema = $required_query->get_output_schema();

				// Validate the required query mapping.
				$validation_result = self::validate_query_mapping( $input_schema, $required_query_input_schema, $required_query_output_schema, $block_title, $required_query_key, $required_query_type );
				if ( is_wp_error( $validation_result ) ) {
					return $validation_result;
				}

				// Add the selector for the required query, noting that the input schema is the display query's input schema.
				$selectors[] = [
					'display_name' => self::get_query_name_from_key( $required_query_key ),
					'image_url' => $required_query->get_image_url(),
					'inputs' => self::map_input_variables( $input_schema ),
					'name' => ucfirst( $required_query_key ),
					'query_key' => $required_query_key,
					'type' => $required_query_type,
					'query_group' => $query_key,
				];

				// Add the required query to the queries array, so it won't be processed again.
				$queries[ $required_query_key ] = $required_query;
			}

			// The query is either a display, or a list query.
			$is_collection = true === ( $output_schema['is_collection'] ?? false );
			$has_required_variables = array_reduce(
				array_column( $input_schema, 'required' ),
				fn( $carry, $required ) => $carry || ( $required ?? true ),
				false
			);

			// Generate the selector for the query.
			$selectors[] = [
				'display_name' => self::get_query_name_from_key( $query_key ),
				'image_url' => $query->get_image_url(),
				'inputs' => self::map_input_variables( $input_schema ),
				'name' => self::DISPLAY_QUERY_KEY === $query->get_type() ? ( $has_required_variables ? 'Manual input' : ( $is_collection ? 'Load collection' : 'Load item' ) ) : ucfirst( $query_key ),
				'query_key' => $query_key,
				'type' => $has_required_variables ? 'manual-input' : 'load-without-input',
				'query_group' => $query_key,
			];
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

	private static function validate_query_mapping( array $to_query_input_schema, array $from_query_input_schema, array $from_query_output_schema, string $block_title, string $from_query_key, string $from_query_type ): WP_Error|bool {
		foreach ( array_keys( $to_query_input_schema ) as $to ) {
			if ( ! isset( $from_query_output_schema['type'][ $to ] ) ) {
				return self::create_error( $block_title, sprintf( 'Cannot map key "%1$s" from %2$s query. The display query for this block requires a "%1$s" key as an input, but it is not present in the output schema for the %2$s query. Try adding a "%1$s" mapping to the output schema for the %2$s query.', esc_html( $to ), $from_query_key ) );
			}
		}

		if ( self::SEARCH_QUERY_KEY === $from_query_type ) {
			$search_input_count = count( array_filter( $from_query_input_schema, function ( array $input_var ): bool {
				return 'ui:search_input' === $input_var['type'];
			} ) );

			if ( 1 !== $search_input_count ) {
				return self::create_error( $block_title, 'A search query must have one input variable with type "ui:search_input"' );
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

	// ToDo: This will not get the first display query, as we register block bindings with the first display query only.
	public static function get_display_query( array $queries ): ?QueryInterface {
		foreach ( $queries as $query_key => $query ) {
			if ( ! $query instanceof QueryInterface ) {
				continue;
			}

			// The migration system in the config store will always handle setting the type to display.
			// Looking at the query key is a fallback, which really should not be needed.
			if ( $query->get_type() === self::DISPLAY_QUERY_KEY || self::DISPLAY_QUERY_KEY === $query_key ) {
				return $query;
			}
		}

		return null;
	}
}
