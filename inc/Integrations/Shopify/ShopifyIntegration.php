<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Shopify;

use RemoteDataBlocks\Config\Query\GraphqlQuery;
use RemoteDataBlocks\WpdbStorage\DataSourceCrud;

use function register_remote_data_block;

class ShopifyIntegration {
	public static function init(): void {
		$data_source_configs = DataSourceCrud::get_configs_by_service( REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE );

		foreach ( $data_source_configs as $config ) {
			$data_source = ShopifyDataSource::from_array( $config );
			self::register_blocks_for_shopify_data_source( $data_source );
		}
	}

	public static function get_queries( ShopifyDataSource $data_source ): array {
		return [
			'shopify_get_product' => GraphqlQuery::from_array( [
				'data_source' => $data_source,
				'input_schema' => [
					'id' => [
						'type' => 'id',
					],
				],
				'output_schema' => [
					'is_collection' => false,
					'type' => [
						'description' => [
							'name' => 'Product description',
							'path' => '$.data.product.descriptionHtml',
							'type' => 'string',
						],
						'details_button_url' => [
							'name' => 'Details URL',
							'generate' => function ( $data ): string {
								return '/path-to-page/' . $data['data']['product']['id'];
							},
							'type' => 'button_url',
						],
						'image_alt_text' => [
							'name' => 'Image Alt Text',
							'path' => '$.data.product.featuredImage.altText',
							'type' => 'image_alt',
						],
						'image_url' => [
							'name' => 'Image URL',
							'path' => '$.data.product.featuredImage.url',
							'type' => 'image_url',
						],
						'price' => [
							'name' => 'Item price',
							'path' => '$.data.product.priceRange.maxVariantPrice.amount',
							'type' => 'currency_in_current_locale',
						],
						'title' => [
							'name' => 'Title',
							'path' => '$.data.product.title',
							'type' => 'string',
						],
						'variant_id' => [
							'name' => 'Variant ID',
							'path' => '$.data.product.variants.edges[0].node.id',
							'type' => 'id',
						],
					],
				],
				'graphql_query' => file_get_contents( __DIR__ . '/Queries/GetProductById.graphql' ),
			] ),
			'shopify_search_products' => GraphqlQuery::from_array( [
				'data_source' => $data_source,
				'input_schema' => [
					'search_terms' => [
						'type' => 'string',
					],
				],
				'output_schema' => [
					'path' => '$.data.products.edges[*]',
					'is_collection' => true,
					'type' => [
						'id' => [
							'name' => 'Product ID',
							'path' => '$.node.id',
							'type' => 'id',
						],
						'image_url' => [
							'name' => 'Item image URL',
							'path' => '$.node.images.edges[0].node.originalSrc',
							'type' => 'image_url',
						],
						'price' => [
							'name' => 'Item price',
							'path' => '$.node.priceRange.maxVariantPrice.amount',
							'type' => 'currency_in_current_locale',
						],
						'title' => [
							'name' => 'Product title',
							'path' => '$.node.title',
							'type' => 'string',
						],
					],
				],
				'graphql_query' => file_get_contents( __DIR__ . '/Queries/SearchProducts.graphql' ),
			] ),
		];
	}

	public static function register_blocks_for_shopify_data_source( ShopifyDataSource $data_source ): void {
		$block_title = $data_source->get_display_name();
		$queries = self::get_queries( $data_source );

		register_remote_data_block( [
			'title' => $block_title,
			'queries' => [
				'display' => $queries['shopify_get_product'],
				'search' => $queries['shopify_search_products'],
			],
			'patterns' => [
				[
					'html' => file_get_contents( __DIR__ . '/Patterns/product-teaser.html' ),
					'role' => 'inner_blocks',
					'title' => 'Shopify Product Teaser',
				],
			],
		] );
	}
}
