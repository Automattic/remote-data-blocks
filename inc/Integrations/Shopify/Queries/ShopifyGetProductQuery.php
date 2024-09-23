<?php

namespace RemoteDataBlocks\Integrations\Shopify\Queries;

use RemoteDataBlocks\Config\QueryContext\GraphqlQueryContext;

class ShopifyGetProductQuery extends GraphqlQueryContext {
	public function define_input_variables(): array {
		return [
			'id' => [
				'type' => 'id',
			],
		];
	}

	public function define_output_variables(): array {
		return [
			'root_path'     => null,
			'is_collection' => false,
			'mappings'      => [
				'description'    => [
					'name' => 'Product description',
					'path' => '$.data.product.descriptionHtml',
					'type' => 'string',
				],
				'title'          => [
					'name' => 'Title',
					'path' => '$.data.product.title',
					'type' => 'string',
				],
				'image_url'      => [
					'name' => 'Image URL',
					'path' => '$.data.product.featuredImage.url',
					'type' => 'image_url',
				],
				'image_alt_text' => [
					'name' => 'Image Alt Text',
					'path' => '$.data.product.featuredImage.altText',
					'type' => 'image_alt',
				],
				'price'          => [
					'name' => 'Item price',
					'path' => '$.data.product.priceRange.maxVariantPrice.amount',
					'type' => 'price',
				],
				'variant_id'     => [
					'name' => 'Variant ID',
					'path' => '$.data.product.variants.edges[0].node.id',
					'type' => 'id',
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
