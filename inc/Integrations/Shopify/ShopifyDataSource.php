<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Shopify;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;

use function plugins_url;

defined( 'ABSPATH' ) || exit();

class ShopifyDataSource extends HttpDataSource {
	protected const SERVICE_NAME = REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE;
	protected const SERVICE_SCHEMA_VERSION = 1;

	protected const SERVICE_SCHEMA = [
		'type' => 'object',
		'properties' => [
			'service' => [
				'type' => 'string',
				'const' => REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE,
			],
			'service_schema_version' => [
				'type' => 'integer',
				'const' => self::SERVICE_SCHEMA_VERSION,
			],
			'access_token' => [ 'type' => 'string' ],
			'store_name' => [ 'type' => 'string' ],
            'display_name' => [
				'type' => 'string',
				'required' => false,
			],
		],
	];

	public function get_display_name(): string {
		return 'Shopify (' . $this->config['display_name'] . ')';
	}

	public function get_endpoint(): string {
		return 'https://' . $this->config['store_name'] . '.myshopify.com/api/2024-04/graphql.json';
	}

	public function get_request_headers(): array {
		return [
			'Content-Type' => 'application/json',
			'X-Shopify-Storefront-Access-Token' => $this->config['access_token'],
		];
	}

	public function get_image_url(): string {
		return plugins_url( './assets/shopify_logo_black.png', __FILE__ );
	}

	public static function create( string $access_token, string $store_name, ?string $display_name = null ): self {
		return parent::from_array([
            'display_name' => $display_name ?? 'Shopify (' . $store_name . ')',
			'service' => REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE,
			'access_token' => $access_token,
			'store_name' => $store_name,
		]);
	}

	public function to_ui_display(): array {
		return [
            'display_name' => $this->get_display_name(),
			'service' => REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE,
			'store_name' => $this->config['store_name'],
			'uuid' => $this->config['uuid'] ?? null,
		];
	}
}
