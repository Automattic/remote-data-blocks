<?php

namespace RemoteDataBlocks\Example\Shopify;

use RemoteDataBlocks\Config\HttpDatasource;
use function plugins_url;

class ShopifyDatasource extends HttpDatasource {
	public function __construct( private string $access_token ) {}

	public function get_endpoint(): string {
		return 'https://stoph-test.myshopify.com/api/2024-04/graphql.json';
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
