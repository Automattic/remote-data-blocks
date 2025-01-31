<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\SalesforceB2C;

use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\WpdbStorage\DataSourceCrud;
use RemoteDataBlocks\Integrations\SalesforceB2C\Auth\SalesforceB2CAuth;
use RemoteDataBlocks\Formatting\StringFormatter;
use WP_Error;

class SalesforceB2CIntegration {
	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_blocks' ], 10, 0 );
	}

	public static function register_blocks(): void {
		$data_source_configs = DataSourceCrud::get_configs_by_service( REMOTE_DATA_BLOCKS_SALESFORCE_B2C_SERVICE );

		foreach ( $data_source_configs as $config ) {
			$data_source = SalesforceB2CDataSource::from_array( $config );
	
			if ( false === ( $config['service_config']['enable_blocks'] ?? true ) ) {
				continue;
			}
			
			self::register_blocks_for_salesforce_data_source( $data_source );
		}
	}

	private static function get_queries( SalesforceB2CDataSource $data_source ): array {
		$base_endpoint = $data_source->get_endpoint();
		$service_config = $data_source->to_array()['service_config'];

		$get_request_headers = function () use ( $base_endpoint, $service_config ): array|WP_Error {
			$access_token = SalesforceB2CAuth::generate_token(
				$base_endpoint,
				$service_config['organization_id'],
				$service_config['client_id'],
				$service_config['client_secret']
			);
			$request_headers = [ 'Content-Type' => 'application/json' ];

			if ( is_wp_error( $access_token ) ) {
				return $access_token;
			}

			return array_merge( $request_headers, [ 'Authorization' => sprintf( 'Bearer %s', $access_token ) ] );
		};

		return [
			'display' => HttpQuery::from_array( [
				'data_source' => $data_source,
				'endpoint' => function ( array $input_variables ) use ( $base_endpoint, $service_config ): string {
					return sprintf(
						'%s/product/shopper-products/v1/organizations/%s/products/%s?siteId=RefArchGlobal',
						$base_endpoint,
						$service_config['organization_id'],
						$input_variables['product_id']
					);
				},
				'input_schema' => [
					'product_id' => [
						'name' => 'Product ID',
						'type' => 'id',
					],
				],
				'output_schema' => [
					'is_collection' => false,
					'type' => [
						'id' => [
							'name' => 'Product ID',
							'path' => '$.id',
							'type' => 'id',
						],
						'name' => [
							'name' => 'Name',
							'path' => '$.name',
							'type' => 'string',
						],
						'longDescription' => [
							'name' => 'Long Description',
							'path' => '$.longDescription',
							'type' => 'string',
						],
						'price' => [
							'name' => 'Price',
							'path' => '$.price',
							'type' => 'string',
						],
						'image_url' => [
							'name' => 'Image URL',
							'path' => '$.imageGroups[0].images[0].link',
							'type' => 'image_url',
						],
						'image_alt_text' => [
							'name' => 'Image Alt Text',
							'path' => '$.imageGroups[0].images[0].alt',
							'type' => 'image_alt',
						],
					],
				],
				'request_headers' => $get_request_headers,
			] ),
			'search' => HttpQuery::from_array( [
				'data_source' => $data_source,
				'endpoint' => function ( array $input_variables ) use ( $base_endpoint, $service_config ): string {
					return sprintf(
						'%s/search/shopper-search/v1/organizations/%s/product-search?siteId=RefArchGlobal&q=%s',
						$base_endpoint,
						$service_config['organization_id'],
						urlencode( $input_variables['search_terms'] )
					);
				},
				'input_schema' => [
					'search_terms' => [
						'type' => 'string',
					],
				],
				'output_schema' => [
					'path' => '$.hits[*]',
					'is_collection' => true,
					'type' => [
						'product_id' => [
							'name' => 'product id',
							'path' => '$.productId',
							'type' => 'id',
						],
						'name' => [
							'name' => 'product name',
							'path' => '$.productName',
							'type' => 'string',
						],
						'price' => [
							'name' => 'item price',
							'path' => '$.price',
							'type' => 'string',
						],
						'image_url' => [
							'name' => 'item image url',
							'path' => '$.image.link',
							'type' => 'image_url',
						],
					],
				],
				'request_headers' => $get_request_headers,
			] ),
		];
	}

	public static function register_blocks_for_salesforce_data_source( SalesforceB2CDataSource $data_source ): void {
		$queries = self::get_queries( $data_source );

		register_remote_data_block(
			[
				'title' => $data_source->get_display_name(),
				'render_query' => [
					'query' => $queries['display'],
				],
				'selection_queries' => [
					[
						'query' => $queries['search'],
						'type' => 'search',
					],
				],
				'overrides' => [
					[
						'display_name' => 'Use Salesforce product from URL',
						'name' => 'salesforce_product_id',
					],
				],
			]
		);

		add_filter( 'query_vars', function ( array $query_vars ): array {
			$query_vars[] = 'utm_content';
			return $query_vars;
		}, 10, 1 );

		add_filter( 'remote_data_blocks_query_input_variables', function ( array $input_variables, array $enabled_overrides ): array {
			if ( true === in_array( 'salesforce_product_id', $enabled_overrides, true ) ) {
				$product_id = get_query_var( 'utm_content' );

				if ( ! empty( $product_id ) ) {
					$input_variables['product_id'] = $product_id;
				}
			}

			return $input_variables;
		}, 10, 2 );
	}

	/**
	 * Get the block registration snippets for the Salesforce B2C integration.
	 *
	 * @param array $data_source_config The data source configuration.
	 * @return array The block registration snippets.
	 */
	public static function get_block_registration_snippets( array $data_source_config ): array {
		$raw_snippet = file_get_contents( __DIR__ . '/templates/block_registration.template' );
		$snippet = strtr( $raw_snippet, [
			'{{DATA_SOURCE_UUID}}' => $data_source_config['uuid'],
			'{{BLOCK_REG_FN_SLUG}}' => StringFormatter::normalize_function_name( [
				$data_source_config['service_config']['display_name'],
			] ),
		] );
		return [ $snippet ];
	}
}
