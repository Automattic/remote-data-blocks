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

		// Get the display query (always use 'display' key)
		if (!isset($user_config['queries']['display'])) {
			return self::create_error($block_title, 'No display query found in queries (must use key "display")');
		}
		$display_query = self::inflate_query($user_config['queries']['display']);
		
		// Initialize queries array with display query
		$queries = [
			self::DISPLAY_QUERY_KEY => $display_query,
		];

		// Build the base configuration
		$config = [
			'description' => '',
			'icon' => $user_config['icon'] ?? 'cloud',
			'name' => $block_name,
			'loop' => $user_config['loop'] ?? false,
			'overrides' => $user_config['overrides'] ?? [],
			'patterns' => [],
			'queries' => $queries,
			'selectors' => [
				[
					'image_url' => $display_query->get_image_url(),
					'inputs' => array_map(function ($slug, $input_var) {
						return [
							'name' => $input_var['name'] ?? $slug,
							'required' => $input_var['required'] ?? true,
							'slug' => $slug,
							'type' => $input_var['type'] ?? 'string',
						];
					}, array_keys($display_query->get_input_schema()), array_values($display_query->get_input_schema())),
					'name' => 'Manual input',
					'query_key' => self::DISPLAY_QUERY_KEY,
					'type' => 'input',
				],
			],
			'title' => $block_title,
		];

		// Add other queries and create selectors based on query_configurations
		foreach ($user_config['queries'] as $key => $query) {
			if ($key === 'display') {
				continue;
			}

			$query = self::inflate_query($query);
			$queries[$key] = $query;

			// Check if this query is configured as a source for another query
			$is_source_query = false;
			foreach ($user_config['query_configurations'] ?? [] as $target_key => $target_config) {
				if ($target_config['source_query'] === $key) {
					$is_source_query = true;
					array_unshift(
						$config['selectors'],
						[
							'image_url' => $query->get_image_url(),
							'inputs' => array_map(function ($slug, $input_var) {
								return [
									'name' => $input_var['name'] ?? $slug,
									'required' => $input_var['required'] ?? false,
									'slug' => $slug,
									'type' => $input_var['type'] ?? 'string',
								];
							}, array_keys($query->get_input_schema()), array_values($query->get_input_schema())),
							'name' => ucfirst($key),
							'query_key' => $key,
							'type' => 'search',
						]
					);
					break;
				}
			}

			// If not a source query and it's a collection query, add it as collection type
			if (!$is_source_query && $key === 'collection') {
				array_unshift(
					$config['selectors'],
					[
						'image_url' => $query->get_image_url(),
						'inputs' => array_map(function ($slug, $input_var) {
							return [
								'name' => $input_var['name'] ?? $slug,
								'required' => $input_var['required'] ?? false,
								'slug' => $slug,
								'type' => $input_var['type'] ?? 'string',
							];
						}, array_keys($query->get_input_schema()), array_values($query->get_input_schema())),
						'name' => 'Collection',
						'query_key' => $key,
						'type' => 'collection',
					]
				);
			}
		}

		// Set the queries on the config
		$config['queries'] = $queries;

		// Register patterns which can be used with the block.
		foreach ($user_config['patterns'] ?? [] as $pattern) {
			$parsed_blocks = parse_blocks($pattern['html']);
			$parsed_blocks = BlockPatterns::add_block_arg_to_bindings($block_name, $parsed_blocks);
			$pattern_content = serialize_blocks($parsed_blocks);

			$pattern_name = self::register_block_pattern($block_name, $pattern['title'], $pattern_content);

			// If the pattern role is specified and recognized, add it to the block configuration.
			$recognized_roles = ['inner_blocks'];
			if (isset($pattern['role']) && in_array($pattern['role'], $recognized_roles, true)) {
				$config['patterns'][$pattern['role']] = $pattern_name;
			}
		}

		ConfigStore::set_block_configuration($block_name, $config);
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
		?string $display_name = null
	): array {
		$input_schema = $query->get_input_schema();
		
		// Convert object input schema to array format
		$inputs = is_array($input_schema) ? array_map(
			function($key, $schema) {
				return array_merge(['slug' => $key], $schema);
			},
			array_keys($input_schema),
			array_values($input_schema)
		) : [];

		return [
			'query_key' => $query_key,
			'type' => $type,
			'name' => $display_name ?? ucfirst($type),
			'inputs' => $inputs,
			'image_url' => null,
		];
	}
}
