<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Editor\BlockManagement;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Config\Block\RemoteDataBlock;
use RemoteDataBlocks\Logging\Logger;
use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Config\Query\QueryInterface;
use RemoteDataBlocks\Editor\BlockPatterns\BlockPatterns;
use RemoteDataBlocks\Logging\LoggerInterface;
use WP_Error;

use function parse_blocks;
use function register_block_pattern;
use function serialize_blocks;

class ConfigRegistry {
	private static LoggerInterface $logger;

	public const DEPRECATED_DISPLAY_QUERY_KEY = 'display';
	public const PLACEHOLDERS_KEY = 'placeholders';
	public const LIST_QUERY_KEY = 'list';
	public const SEARCH_QUERY_KEY = 'search';
	public const QUERIES_KEY = 'queries';

	public static function init( ?LoggerInterface $logger = null ): void {
		self::$logger = $logger ?? new Logger();
		ConfigStore::init( self::$logger );
	}

	public static function register_block( array $block_config = [] ): bool|WP_Error {
		// Validate the provided block configuration.
		$block_config = RemoteDataBlock::from_array( $block_config );

		if ( is_wp_error( $block_config ) ) {
			self::$logger->error( $block_config->get_error_message() );
			return $block_config;
		}

		// Check if the block has already been registered.
		$block_config = $block_config->to_array();
		$block_title = $block_config['title'];

		// If block name not informed, build it from the title.
		$block_name_seed = $block_config['name'] ?? $block_title;
		$block_name = ConfigStore::get_block_name( $block_name_seed );

		// Check if the block has already been registered.
		if ( ConfigStore::is_registered_block( $block_name ) || ConfigStore::is_registered_block_by_title( $block_title ) ) {
			return self::create_error( $block_title, sprintf( 'Block %s has already been registered', $block_name ) );
		}

		// Ensure that the block has queries.
		if ( empty( $block_config[ self::QUERIES_KEY ] ) ) {
			return self::create_error( $block_title, 'Block configuration must have a non-empty "queries" array' );
		}

		$queries = [];
		$display_queries_to_selectors_map = [];

		// Either use the placeholders from the config, or build them from the queries.
		// Placeholders are meant to determine the queries that'll be visually represented
		// in the block's UI upon initial insertion.
		if ( ! empty( $block_config[ self::PLACEHOLDERS_KEY ] ) ) {
			$placeholders = $block_config[ self::PLACEHOLDERS_KEY ];
			// Pre-validate the placeholders, to ensure they exist.
			foreach ( $placeholders as $placeholder ) {
				if ( ! isset( $block_config[ self::QUERIES_KEY ][ $placeholder['query_key'] ] ) ) {
					return self::create_error( $block_title, sprintf( 'Query "%s" not found for placeholder "%s"', $placeholder['query_key'], $placeholder['name'] ) );
				}
			}
		} else {
			$placeholders = [];
			// Using array_keys here triggers a psalm error, so it's set to $_ instead.
			// Supressing the psalm error is a not a good idea, so instead this is the better solution.
			// ToDo: Fix the psalm error, and see if array_keys could be used here again.
			foreach ( $block_config[ self::QUERIES_KEY ] as $query_key => $_ ) {
				$placeholders[] = [
					'name' => self::get_query_name_from_key( $query_key ),
					'query_key' => $query_key,
				];
			}
		}

		foreach ( $placeholders as $placeholder ) {
			$selectors = [];

			$placeholder_query_key = $placeholder['query_key'];
			$placeholder_query = self::inflate_query( $block_config[ self::QUERIES_KEY ][ $placeholder_query_key ] );
			$queries[ $placeholder_query_key ] = $placeholder_query;

			$placeholder_query_input_schema = $placeholder_query->get_input_schema();
			$placeholder_query_output_schema = $placeholder_query->get_output_schema();

			// We first generate the manual input selector for the placeholder query.
			$is_collection = true === ( $placeholder_query_output_schema['is_collection'] ?? false );
			$has_required_variables = array_reduce(
				array_column( $placeholder_query_input_schema, 'required' ),
				fn( $carry, $required ) => $carry || ( $required ?? true ),
				false
			);

			$selector_config = [
				'image_url' => $placeholder_query->get_image_url(),
				'inputs' => self::map_input_variables( $placeholder_query_input_schema ),
				'name' => $has_required_variables ? 'Manual input' : ( $is_collection ? 'Load collection' : 'Load item' ),
				'query_key' => $placeholder_query_key,
				'type' => $has_required_variables ? 'manual-input' : 'load-without-input',
			];

			$selectors[] = $selector_config;

			// We run through all the queries to find compatible selectors.
			foreach ( $block_config[ self::QUERIES_KEY ] as $selector_query_key => $selector_query ) {
				// Don't match the placeholder to itself, as that's already been done.
				if ( $selector_query_key === $placeholder_query_key ) {
					continue;
				}

				$selector_query = self::inflate_query( $selector_query );
				$queries[ $selector_query_key ] = $selector_query;

				$selector_query_input_schema = $selector_query->get_input_schema();
				$selector_query_output_schema = $selector_query->get_output_schema();

				// Infer the type of the selector query.
				// ToDo: Add support for multiple types.
				$inferred_selector_query_type = self::infer_query_type( $selector_query_input_schema, $selector_query_output_schema );
				if ( 'unknown' === $inferred_selector_query_type ) {
					continue;
				}

				// If the output schema is not an array, skip.
				if ( ! is_array( $selector_query_output_schema['type'] ) ) {
					continue;
				}

				// Find the fields that are present in both the selector's output schema and the placeholder's input schema.
				$intersecting_keys = array_intersect_key( $selector_query_output_schema['type'], $placeholder_query_input_schema );

				// Skip this, if they don't intersect.
				if ( empty( $intersecting_keys ) ) {
					continue;
				}

				// Ensure the fields found have the same name and type in both the schemas.
				$valid_intersecting_keys = self::validate_selector_query_mapping( $intersecting_keys, $placeholder_query_input_schema, $selector_query_output_schema );
				if ( empty( $valid_intersecting_keys ) ) {
					continue;
				}

				// Now we generate the selector query's config as a selector for the placeholder query.
				$selector_config = [
					'image_url' => $selector_query->get_image_url(),
					'inputs' => self::map_input_variables( $selector_query_input_schema ),
					'name' => self::get_query_name_from_key( $selector_query_key ),
					'query_key' => $selector_query_key,
					'type' => $inferred_selector_query_type,
				];

				// Add the selector to the beginning of the selectors array.
				array_unshift(
					$selectors,
					$selector_config
				);
			}

			$display_queries_to_selectors_map[ $placeholder_query_key ] = [
				'name' => $placeholder['name'],
				'selectors' => $selectors,
			];
		}

		// Build the block configuration.
		$config = [
			'description' => '',
			'icon' => $block_config['icon'] ?? 'cloud',
			'instructions' => $block_config['instructions'] ?? null,
			'name' => $block_name,
			'overrides' => $block_config['overrides'] ?? [],
			'patterns' => [],
			'queries' => $queries,
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

	private static function register_block_pattern( string $block_name, string $pattern_title, string $pattern_content ): string {
		// Add the block arg to any bindings present in the pattern.
		$pattern_name = 'remote-data-blocks/' . sanitize_title_with_dashes( $pattern_title, '', 'save' );

		// Create the pattern properties, allowing overrides via pattern options.
		$pattern_properties = [
			'blockTypes' => [ $block_name ],
			'categories' => [ 'remote-data-blocks' ],
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

	private static function infer_query_type( array $input_schema, array $output_schema ): string {
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

		// This will happen if a query has not been configured correctly as a search or list query.
		// So we error out, to replace the previous way of validating when the type was set.
		return 'unknown';
	}
}
