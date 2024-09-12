<?php

namespace RemoteDataBlocks\Config;

use function plugins_url;

defined( 'ABSPATH' ) || exit();

class ShopifyDatasource extends HttpDatasource {
	public function __construct( private string $access_token, private string $store_name ) {}

	public function get_store_name(): string {
		return $this->store_name;
	}

	public function get_display_name(): string {
		return 'Shopify (' . $this->store_name . ')';
	}

	public function get_uid(): string {
		return hash( 'sha256', $this->store_name );
	}

	public function get_endpoint(): string {
		return 'https://' . $this->store_name . '.myshopify.com/api/2024-04/graphql.json';
	}

	public function get_request_headers(): array {
		return [
			'Content-Type'                      => 'application/json',
			'X-Shopify-Storefront-Access-Token' => $this->access_token,
		];
	}

	public function get_image_url(): string {
		return plugins_url( '../../assets/shopify_logo_black.png', __FILE__ );
	}
}
