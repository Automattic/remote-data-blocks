<?php

namespace RemoteDataBlocks\Integrations\Shopify;

use RemoteDataBlocks\Config\ArraySerializableInterface;
use RemoteDataBlocks\Config\Datasource\DatasourceInterface;
use RemoteDataBlocks\Config\Datasource\HttpDatasource;

use function plugins_url;

defined( 'ABSPATH' ) || exit();

class ShopifyDatasource extends HttpDatasource implements ArraySerializableInterface {
	private const SERVICE_SCHEMA = [
		'type'       => 'object',
		'properties' => [
			'access_token' => [ 'type' => 'string' ],
			'store_name'   => [ 'type' => 'string' ],
		],
	];

	public function get_store_name(): string {
		return $this->config['store_name'];
	}

	public function get_display_name(): string {
		return 'Shopify (' . $this->config['store_name'] . ')';
	}

	public function get_endpoint(): string {
		return 'https://' . $this->config['store_name'] . '.myshopify.com/api/2024-04/graphql.json';
	}

	public function get_request_headers(): array {
		return [
			'Content-Type'                      => 'application/json',
			'X-Shopify-Storefront-Access-Token' => $this->config['access_token'],
		];
	}

	public function get_image_url(): string {
		return plugins_url( '../../assets/shopify_logo_black.png', __FILE__ );
	}

	public static function get_config_schema(): array {
		return array_merge( DatasourceInterface::BASE_SCHEMA, self::SERVICE_SCHEMA );
	}
}
