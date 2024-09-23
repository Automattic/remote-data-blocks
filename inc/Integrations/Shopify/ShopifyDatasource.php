<?php

namespace RemoteDataBlocks\Integrations\Shopify;

use RemoteDataBlocks\Config\ArraySerializableInterface;
use RemoteDataBlocks\Config\Datasource\HttpDatasource;

use function plugins_url;

defined( 'ABSPATH' ) || exit();

class ShopifyDatasource extends HttpDatasource implements ArraySerializableInterface {
	protected const SERVICE_NAME           = REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE;
	protected const SERVICE_SCHEMA_VERSION = 1;

	protected const SERVICE_SCHEMA = [
		'type'       => 'object',
		'properties' => [
			'service'                => [
				'type'  => 'string',
				'const' => REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE,
			],
			'service_schema_version' => [
				'type'  => 'integer',
				'const' => self::SERVICE_SCHEMA_VERSION,
			],
			'access_token'           => [ 'type' => 'string' ],
			'store_name'             => [ 'type' => 'string' ],
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

	public static function create( string $access_token, string $store_name ): self {
		return parent::from_array([
			'service'                => REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE,
			'service_schema_version' => self::SERVICE_SCHEMA_VERSION,
			'uuid'                   => wp_generate_uuid4(),
			'access_token'           => $access_token,
			'store_name'             => $store_name,
		]);
	}

	public function to_ui_display(): array {
		return [
			'slug'    => $this->get_display_name(),
			'service' => REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE,
			'store'   => $this->config['store_name'],
		];
	}
}
