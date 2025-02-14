<?php declare(strict_types = 1);

namespace RemoteDataBlocks\IntegrationTests\Blocks;

use RDBTestCase;

class BlockWithQueryAsDataSourceTest extends RDBTestCase {
	public function testBlockWithQueryAsDataSourceRenders(): void {
		$test_api_response = [
			'id' => 12345,
			'name' => 'Crayons',
			'price' => '0.99',
			'details' => [
				'variants' => [
					[
						'name' => 'Burnt Sienna',
						'code' => 'burnt-sienna',
					],
					[
						'name' => 'Periwinkle',
						'code' => 'periwinkle',
					],
					[
						'name' => 'Fuscia',
						'code' => 'fuscia',
					],
				],
			],
		];

		$toy_query = [
			'__class' => 'RemoteDataBlocks\\Config\\Query\\HttpQuery',
			'data_source' => [
				'__class' => 'RemoteDataBlocks\\Config\\DataSource\\HttpDataSource',
				'service_config' => [
					'__version' => 1,
					'display_name' => 'Test API',
					// Mocked query runner will not actually make a request to the endpoint URL.
					'endpoint' => 'https://example.com/not-a-real-api',
				],
			],
			'output_schema' => [
				'is_collection' => false,
				'type' => [
					'id' => [
						'name' => 'ID',
						'path' => '$.id',
						'type' => 'string',
					],
					'name' => [
						'name' => 'Name',
						'path' => '$.name',
						'type' => 'string',
					],
					'price' => [
						'name' => 'Price',
						'path' => '$.price',
						'type' => 'currency_in_current_locale',
					],
					'variants' => [
						'is_collection' => true,
						'name' => 'Types',
						'path' => '$.details.variants[*]',
						'type' => [
							'name' => [
								'name' => 'Name',
								'path' => '$.name',
								'type' => 'string',
							],
							'code' => [
								'name' => 'Code',
								'path' => '$.code',
								'type' => 'string',
							],
						],
					],
				],
			],
			'query_runner' => $this->get_query_runner_with_response( $test_api_response ),
		];

		$registration_result = register_remote_data_block( [
			'title' => 'Toy',
			'render_query' => [
				'query' => $toy_query,
			],
		] );

		$this->assertTrue( $registration_result );
		$this->register_remote_data_block_from_block_title( 'Toy' );

		$registration_result = register_remote_data_block( [
			'title' => 'Toy Variant',
			'render_query' => [
				'loop' => true,
				'query' => [
					'__class' => 'RemoteDataBlocks\\Config\\Query\\HttpQuery',
					'data_source' => $toy_query,
					'output_schema' => [
						'is_collection' => true,
						'path' => '$.results[0].result.variants.value[*].result',
						'type' => [
							'name' => [
								'name' => 'Name',
								'path' => '$.name.value',
								'type' => 'string',
							],
							'code' => [
								'name' => 'Code',
								'path' => '$.code.value',
								'type' => 'string',
							],
						],
					],
				],
			],
		] );

		$this->assertTrue( $registration_result );
		$this->register_remote_data_block_from_block_title( 'Toy Variant' );

		$result_html = do_blocks('
			<!-- wp:remote-data-blocks/toy {"remoteData":{"blockName":"remote-data-blocks/toy","queryInput":{}}} -->
			<div class="wp-block-remote-data-blocks-toy rdb-container">
				<!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/toy","field":"name"}}},"name":"Name"}} -->
				<h2 id="field-name" class="wp-block-heading"></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/toy","field":"price"}}},"name":"Price"}} -->
				<p id="field-price"></p>
				<!-- /wp:paragraph -->

				<!-- wp:heading -->
				<h3 class="wp-block-heading">Types</h3>
				<!-- /wp:heading -->

				<!-- wp:remote-data-blocks/toy-variant {"remoteData":{"blockName":"remote-data-blocks/toy-variant","queryInput":{}}} -->
				<div class="wp-block-remote-data-blocks-toy-variant rdb-container">
					<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/toy-variant","field":"name"}}},"name":"Name"}} -->
					<p class="field-variant-name"></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/toy-variant","field":"code"}}},"name":"Code"}} -->
					<p class="field-variant-code"></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:remote-data-blocks/toy-variant -->

			</div>
			<!-- /wp:remote-data-blocks/toy -->
		');

		$dom = self::load_html( $result_html );
		$this->assertDomIdHasTextContent( $dom, 'field-name', 'Crayons' );
		$this->assertDomIdHasTextContent( $dom, 'field-price', '$0.99' );

		$variant_names = $this->get_dom_elements_by_html_class( $dom, 'field-variant-name' );
		$this->assertCount( 3, $variant_names, sprintf( "Should be 3 matching nodes with class 'field-variant-name' but %d found.", count( $variant_names ) ) );
		$this->assertEquals( 'Burnt Sienna', $variant_names[0]->textContent );
		$this->assertEquals( 'Periwinkle', $variant_names[1]->textContent );
		$this->assertEquals( 'Fuscia', $variant_names[2]->textContent );

		$variant_codes = $this->get_dom_elements_by_html_class( $dom, 'field-variant-code' );
		$this->assertCount( 3, $variant_codes, sprintf( "Should be 3 matching nodes with class 'field-variant-code' but %d found.", count( $variant_codes ) ) );
		$this->assertEquals( 'burnt-sienna', $variant_codes[0]->textContent );
		$this->assertEquals( 'periwinkle', $variant_codes[1]->textContent );
		$this->assertEquals( 'fuscia', $variant_codes[2]->textContent );
	}
}
