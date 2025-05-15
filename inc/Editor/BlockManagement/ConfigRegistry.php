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

	public static function init( ?LoggerInterface $logger = null ): void {
		self::$logger = $logger ?? new Logger();
		ConfigStore::init( self::$logger );
	}

	public static function register_block( array $user_config = [] ): bool|WP_Error {
		// Validate the provided user configuration.
		$schema = ConfigSchemas::get_remote_data_block_config_schema();
		$validator = new Validator( $schema, static::class, '$user_config' );
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

		$queries = [];
		$selectors = [];
		$required_queries = [];

		// go over the queries, inflate each one, get the required query, skip if it's not present and then make a list out of it.
		foreach ( $user_config['queries'] as $query_key => $query ) {
			$query = self::inflate_query( $query );

			// ToDo: Add a validation step to check if the required query is present in the user_config['queries'] array.
			if ( $query->get_required_query() && ! empty( $query->get_required_query() ) ) {
				// Add the mapping of the required query to the required queries array.
				$required_queries[ $query->get_required_query() ] = $query_key;
			}
		}

		foreach ( $user_config['queries'] as $query_key => $query ) {
			$query = self::inflate_query( $query );
			$queries[ $query_key ] = $query;
			$input_schema = $query->get_input_schema();
			$output_schema = $query->get_output_schema();

			if ( isset( $required_queries[ $query_key ] ) ) {
				array_unshift(
					$selectors,
					[
						'display_name' => self::get_query_name_from_key( $query_key ),
						'image_url' => $query->get_image_url(),
						'inputs' => self::map_input_variables( $input_schema ),
						'name' => ucfirst( $query_key ),
						'query_key' => $query_key,
						'type' => $query->get_type(),
						'query_group' => $required_queries[ $query_key ],
					]
				);
			} else {
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
					// ToDo: Could this be removed so we don't need to assume special logic for the display query?
					'name' => self::DISPLAY_QUERY_KEY === $query->get_type() ? ( $has_required_variables ? 'Manual input' : ( $is_collection ? 'Load collection' : 'Load item' ) ) : ucfirst( $query_key ),
					'query_key' => $query_key,
					'type' => $has_required_variables ? 'manual-input' : 'load-without-input',
					'query_group' => $query_key,
				];
			}
		}

		$config = [
			'description' => '',
			'icon' => $user_config['icon'] ?? 'cloud',
			'instructions' => $user_config['instructions'] ?? null,
			'name' => $block_name,
			'overrides' => $user_config['overrides'] ?? [],
			'patterns' => [],
			'queries' => $queries,
			'selectors' => $selectors,
			'title' => $block_title,
		];

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

	// ToDo: The source query is the from query, and the target query is the to query when calling this. So, the display query is the to query and the source query for the data is the from query. The from query's key is the from_query_key.
	private static function validate_query_mapping( array $to_query_input_schema, array $from_query_output_schema, string $block_title, string $from_query_key ): WP_Error|bool {
		foreach ( array_keys( $to_query_input_schema ) as $to ) {
			if ( ! isset( $from_query_output_schema['type'][ $to ] ) ) {
				return self::create_error( $block_title, sprintf( 'Cannot map key "%1$s" from %2$s query. The display query for this block requires a "%1$s" key as an input, but it is not present in the output schema for the %2$s query. Try adding a "%1$s" mapping to the output schema for the %2$s query.', esc_html( $to ), $from_query_key ) );
			}
		}

		if ( self::SEARCH_QUERY_KEY === $from_query_key ) {
			$search_input_count = count( array_filter( $to_query_input_schema, function ( array $input_var ): bool {
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

	public static function get_display_query( array $queries ): ?QueryInterface {
		foreach ( $queries as $query ) {
			if ( $query instanceof QueryInterface && $query->get_type() === self::DISPLAY_QUERY_KEY ) {
				return $query;
			}
		}

		return null;
	}
}
