<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Shopify;

use RemoteDataBlocks\Config\QueryContext\GraphqlMutationContext;

class ShopifyCreateCartMutation extends GraphqlMutationContext {
	public function get_output_schema(): array {
		return [
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
	}

	public function get_mutation(): string {
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
