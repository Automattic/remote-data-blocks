<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Shopify\Queries;

use RemoteDataBlocks\Config\QueryContext\GraphqlQueryContext;

class ShopifyGetProductQuery extends GraphqlQueryContext {
	public function get_input_schema(): array {
		return [
			'id' => [
				'type' => 'id',
				'overrides' => [
					[
						'target' => 'product-details',
						'type' => 'url',
					],
				],
			],
		];
	}

	public function get_output_schema(): array {
		return [
			'root_path' => null,
			'is_collection' => false,
			'mappings' => [
				'description' => [
					'name' => 'Product description',
					'path' => '$.data.product.descriptionHtml',
					'type' => 'string',
				],
				'title' => [
					'name' => 'Title',
					'path' => '$.data.product.title',
					'type' => 'string',
				],
				'image_url' => [
					'name' => 'Image URL',
					'path' => '$.data.product.featuredImage.url',
					'type' => 'image_url',
				],
				'image_alt_text' => [
					'name' => 'Image Alt Text',
					'path' => '$.data.product.featuredImage.altText',
					'type' => 'image_alt',
				],
				'price' => [
					'name' => 'Item price',
					'path' => '$.data.product.priceRange.maxVariantPrice.amount',
					'type' => 'currency',
				],
				'variant_id' => [
					'name' => 'Variant ID',
					'path' => '$.data.product.variants.edges[0].node.id',
					'type' => 'id',
				],
				'details_button_url' => [
					'name' => 'Details URL',
					'generate' => function ( $data ) {
						return '/path-to-page/' . $data['data']['product']['id'];
					},
					'type' => 'button_url',
				],
			],
		];
	}

	public function get_query(): string {
		return 'query GetProductById($id: ID!) {
			product(id: $id) {
				id
				descriptionHtml
				title
				featuredImage {
					url
					altText
				}
				priceRange {
					maxVariantPrice {
						amount
					}
				}
				variants(first: 10) {
					edges {
						node {
							id
							availableForSale
							image {
								url
							}
							sku
							title
						}
					}
				}
			}
		}';
	}
}
