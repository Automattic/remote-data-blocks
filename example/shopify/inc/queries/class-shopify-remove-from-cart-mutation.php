<?php

namespace RemoteDataBlocks\Example\Shopify;

use RemoteDataBlocks\Config\QueryContext\GraphqlQueryContext;

class ShopifyRemoveFromCartMutation extends GraphqlQueryContext {
	public function define_input_variables(): array {
		return [
			'cart_id' => [
				'type' => 'id',
			],
			'line_id' => [
				'type' => 'id',
			],
		];
	}

	public function define_output_variables(): array {
		return [
			'root_path'     => '$.data.cartLinesAdd.cart.lines.edges[*].node',
			'is_collection' => true,
			'mappings'      => [
				'id'            => [
					'name' => 'Line ID',
					'path' => '$.id',
					'type' => 'id',
				],
				'quantity'      => [
					'name' => 'Quantity',
					'path' => '$.quantity',
					'type' => 'string',
				],
				'variant_id'    => [
					'name' => 'Variant ID',
					'path' => '$.merchandise.id',
					'type' => 'id',
				],
				'variant_title' => [
					'name' => 'Title',
					'path' => '$.merchandise.title',
					'type' => 'string',
				],
			],
		];
	}

	public function get_query(): string {
		return '
			mutation RemoveProductFromCart( $cart_id: ID!, $line_id: ID! ){
				cartLinesAdd(cartId: $cart_id, lineIds: [ $line_id ] ) {
					cart {
						id
						lines(first: 10) {
							edges {
								node {
									id
									quantity
									merchandise {
										... on ProductVariant {
											id
											title
											product {
												title
											}
										}
									}
								}
							}
						}
					}
				}
			}';
	}
}
