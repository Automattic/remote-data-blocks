<?php declare(strict_types = 1);

namespace RemoteDataBlocks\IntegrationTests\Blocks;

use RDBTestCase;

class BlockWithNestedConfigTest extends RDBTestCase {
	public function testBlockWithNestedConfigRenders(): void {
		$test_api_response = [
			'id' => 12345,
			'name' => 'Test Product',
			'price' => '19.99',
		];

		$registration_result = register_remote_data_block( [
			'title' => 'Test Product API',
			'render_query' => [
				'query' => [
					'__class' => 'RemoteDataBlocks\\Config\\Query\\HttpQuery',
					'data_source' => [
						'__class' => 'RemoteDataBlocks\\Config\\DataSource\\HttpDataSource',
						'display_name' => 'Test API',
						// Mocked query runner will not actually make a request to the endpoint URL.
						'endpoint' => 'https://example.com/not-a-real-api',
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
						],
					],
					'query_runner' => $this->get_query_runner_with_response( $test_api_response ),
				],
			],
		] );

		$this->assertTrue( $registration_result );
		$this->register_remote_data_block_from_block_title( 'Test Product API' );

		$result_html = do_blocks('
			<!-- wp:remote-data-blocks/test-product-api {"remoteData":{"blockName":"remote-data-blocks/test-product-api","queryInput":{}}} -->
			<div class="wp-block-remote-data-blocks-test-product-api rdb-container">
				<!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/test-product-api","field":"name"}}},"name":"Name"}} -->
				<h2 id="field-name" class="wp-block-heading"></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/test-product-api","field":"price"}}},"name":"Price"}} -->
				<p id="field-price"></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:remote-data-blocks/test-product-api -->
		');

		$dom = self::load_html( $result_html );
		$this->assertDomIdHasTextContent( $dom, 'field-name', 'Test Product' );
		$this->assertDomIdHasTextContent( $dom, 'field-price', '$19.99' );
	}
}
