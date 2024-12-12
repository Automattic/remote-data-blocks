<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\SalesforceB2C;

use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\WpdbStorage\DataSourceCrud;
use RemoteDataBlocks\Integrations\SalesforceB2C\Auth\SalesforceB2CAuth;
use WP_Error;

class SalesforceB2CIntegration {
	public static function init(): void {
		$data_source_configs = DataSourceCrud::get_configs_by_service( REMOTE_DATA_BLOCKS_SALESFORCE_B2C_SERVICE );

		foreach ( $data_source_configs as $config ) {
			$data_source = SalesforceB2CDataSource::from_array( $config );
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
							'path' => '$.productid',
							'type' => 'id',
						],
						'name' => [
							'name' => 'product name',
							'path' => '$.productname',
							'type' => 'string',
						],
						'price' => [
							'name' => 'item price',
							'path' => '$.price',
							'type' => 'price',
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
		register_remote_data_block(
			[
				'title' => $data_source->get_display_name(),
				'queries' => self::get_queries( $data_source ),
				'query_input_overrides' => [
					[
						'query' => 'display',
						'source' => 'utm_content',
						'source_type' => 'query_var',
						'target' => 'product_id',
						'target_type' => 'input_var',
					],
				],
			]
		);
	}
}
