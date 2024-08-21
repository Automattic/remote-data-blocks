<?php

namespace RemoteDataBlocks\Config;

use RemoteDataBlocks\Config\HttpDatasource;
use function plugins_url;

class ShopifyDatasource extends HttpDatasource {
	public function __construct( private string $access_token, private string $store_name ) {}

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
