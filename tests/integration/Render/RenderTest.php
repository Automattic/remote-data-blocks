<?php declare(strict_types = 1);

namespace RemoteDataBlocks\IntegrationTests\Render;

use RDBTestCase;

class RenderTest extends RDBTestCase {
	public function testRenderQueryBlockBindingsRender(): void {
		$test_api_response = [
			'post code' => 12345,
			'places' => [
				[
					'place name' => 'Test City',
					'state' => 'Test State',
				],
			],
		];

		$test_output_schema = [
			'is_collection' => false,
			'type' => [
				'zip_code' => [
					'name' => 'Zip Code',
					'path' => '$["post code"]',
					'type' => 'string',
				],
				'city' => [
					'name' => 'City',
					'path' => '$.places[0]["place name"]',
					'type' => 'string',
				],
				'state' => [
					'name' => 'State',
					'path' => '$.places[0].state',
					'type' => 'string',
				],
			],
		];

		$this->register_mocked_data_block( 'Test ZIP API', $test_api_response, $test_output_schema );

		$result_html = do_blocks('
			<!-- wp:remote-data-blocks/test-zip-api {"remoteData":{"blockName":"remote-data-blocks/test-zip-api","queryInput":{"zip_code":"12345"}}} -->
			<div class="wp-block-remote-data-blocks-test-zip-api rdb-container">
				<!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/test-zip-api","field":"zip_code"}}},"name":"Zip Code"}} -->
				<h2 id="field-zip-code" class="wp-block-heading"></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/test-zip-api","field":"city"}}},"name":"City"}} -->
				<p id="field-city"></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/test-zip-api","field":"state"}}},"name":"State"}} -->
				<p id="field-state"></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:remote-data-blocks/test-zip-api -->
		');

		$dom = self::load_html( $result_html );
		$this->assertDomIdHasTextContent( $dom, 'field-zip-code', '12345' );
		$this->assertDomIdHasTextContent( $dom, 'field-city', 'Test City' );
		$this->assertDomIdHasTextContent( $dom, 'field-state', 'Test State' );
	}
}
