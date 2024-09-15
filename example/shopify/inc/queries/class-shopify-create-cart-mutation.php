<?php

namespace RemoteDataBlocks\Example\Shopify;

use RemoteDataBlocks\Config\QueryContext\GraphqlQueryContext;

class ShopifyCreateCartMutation extends GraphqlQueryContext {
	public array $input_variables = [];

	public array $output_variables = [
		'root_path'     => '$.data.cartCreate.cart',
		'is_collection' => false,
		'mappings'      => [
			'cart_id'      => [
				'name' => 'Shopping cart ID',
				'path' => '$.id',
				'type' => 'id',
			],
			'checkout_url' => [
				'name' => 'Shopping cart checkout URL',
				'path' => '$.checkoutUrl',
				'type' => 'string',
			],
		],
	];

	public function get_query(): string {
		return '
			mutation CreateShoppingCart {
				cartCreate {
					cart {
						id
						checkoutUrl
					}
				}
			}';
	}
}
