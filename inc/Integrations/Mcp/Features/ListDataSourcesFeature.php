<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Mcp\Features;

use RemoteDataBlocks\Store\DataSource\DataSourceConfigManager;
use RemoteDataBlocks\Snippet\Snippet;
use WP_REST_Request;

use function add_action;
use function wp_register_feature;

defined( 'ABSPATH' ) || exit();

/**
 * Feature that lists all Remote Data Block data sources through the WP Feature API.
 *
 * This feature provides access to all configured data sources including those from
 * code, constants, and storage, with optional filtering capabilities and code snippets.
 */
class ListDataSourcesFeature {

	/**
	 * Initialize and register the feature with the WP Feature API.
	 */
	public static function init(): void {
		// Hook into the feature API initialization
		add_action( 'wp_feature_api_init', [ __CLASS__, 'register_feature' ] );
	}

	/**
	 * Register the list data sources feature.
	 */
	public static function register_feature(): void {
		/**
		 * @psalm-suppress UndefinedFunction
		 */
		wp_register_feature( [
			'id' => 'rdb-list-data-sources',
			'name' => 'List Remote Data Block data sources',
			'description' => 'List all Remote Data Block data sources with optional filtering by service type or block enablement status, including code snippets',
			'callback' => [ __CLASS__, 'handle_request' ],
			'is_eligible' => '__return_true',
			'permission_callback' => '__return_true',
			'input_schema' => [
				'type' => 'object',
				'properties' => [
					'service' => [
						'type' => 'string',
						'description' => 'Filter by service name (e.g., "airtable", "google-sheets", "shopify")',
						'enum' => [
							'airtable',
							'generic-http',
							'google-sheets',
							'shopify',
						],
					],
					'enable_blocks' => [
						'type' => 'boolean',
						'description' => 'Filter by blocks enabled status (true shows only sources with blocks enabled, false shows only disabled)',
					],
					'include_snippets' => [
						'type' => 'boolean',
						'description' => 'Include code snippets for each data source (default: true)',
						'default' => true,
					],
				],
				'additionalProperties' => false,
			],
		] );
	}

	/**
	 * Handle the feature request and return data sources.
	 */
	public static function handle_request( WP_REST_Request $request ): array {
		// Validate and sanitize parameters
		$params = $request->get_params();
		$filters = [];

		if ( isset( $params['service'] ) && is_string( $params['service'] ) ) {
			$filters['service'] = sanitize_text_field( $params['service'] );
		}

		if ( isset( $params['enable_blocks'] ) && is_bool( $params['enable_blocks'] ) ) {
			$filters['enable_blocks'] = $params['enable_blocks'];
		}

		$include_snippets = $params['include_snippets'] ?? true;
		$data_sources = DataSourceConfigManager::get_all( $filters );

		// Handle errors
		if ( is_wp_error( $data_sources ) ) {
			return [
				'success' => false,
				'error' => $data_sources->get_error_message(),
			];
		}

		return [
			'success' => true,
			'data' => array_map(
				function ( array $data_source ) use ( $include_snippets ): array {
					return self::format_data_source( $data_source, $include_snippets );
				},
				$data_sources
			),
			'count' => count( $data_sources ),
		];
	}

	/**
	 * Format data source
	 */
	private static function format_data_source( array $data_source, bool $include_snippets = true ): array {
		// Add code snippets if requested and data source has UUID
		$code_snippets = [];
		if ( $include_snippets && isset( $data_source['uuid'] ) ) {
			$code_snippets = Snippet::generate_snippets( $data_source['uuid'] );

			// Only add snippets if generation was successful
			if ( is_wp_error( $code_snippets ) ) {
				$code_snippets = [];
			}
		}

		return [
			'uuid' => $data_source['uuid'] ?? null,
			'display_name' => $data_source['service_config']['display_name'] ?? $data_source['display_name'] ?? null,
			'code_snippets' => array_map(
				function ( Snippet $snippet ): array {
					return $snippet->jsonSerialize();
				},
				$code_snippets
			),
			'config_source' => $data_source['config_source'] ?? 'unknown',
			'service' => $data_source['service'] ?? null,
		];
	}
}
