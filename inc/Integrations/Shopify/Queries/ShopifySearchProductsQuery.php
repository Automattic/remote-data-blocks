<?php

namespace RemoteDataBlocks\Integrations\Shopify\Queries;

use RemoteDataBlocks\Config\QueryContext\GraphqlQueryContext;

class ShopifySearchProductsQuery extends GraphqlQueryContext {
	public function define_input_variables(): array {
		return [
			'search_terms' => [
				'type' => 'string',
			],
		];
	}

	public function define_output_variables(): array {
		return [
			'root_path'     => '$.data.products.edges[*]',
			'is_collection' => true,
			'mappings'      => [
				'id'        => [
					'name' => 'Product ID',
					'path' => '$.node.id',
					'type' => 'id',
				],
				'title'     => [
					'name' => 'Product title',
					'path' => '$.node.title',
					'type' => 'string',
				],
				'price'     => [
					'name' => 'Item price',
					'path' => '$.node.priceRange.maxVariantPrice.amount',
					'type' => 'price',
				],
				'image_url' => [
					'name' => 'Item image URL',
					'path' => '$.node.images.edges[0].node.originalSrc',
					'type' => 'image_url',
				],
			],
		];
	}

	public function get_query(): string {
		return 'query SearchProducts($search_terms: String!) {
			products(first: 10, query: $search_terms, sortKey: BEST_SELLING) {
				edges {
					node {
						id
						title
						descriptionHtml
						priceRange {
							maxVariantPrice {
								amount
							}
						}
						images(first: 1) {
							edges {
								node {
									originalSrc
								}
							}
						}
					}
				}
			}
		}';
	}

	public function get_query_name(): string {
		return 'Search products';
	}
}
