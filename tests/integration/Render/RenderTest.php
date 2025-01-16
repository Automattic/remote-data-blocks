<?php declare(strict_types = 1);

namespace RemoteDataBlocks\IntegrationTests\Render;

use DOMDocument;
use DOMXPath;
use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Config\Query\HttpQueryInterface;
use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use RemoteDataBlocks\Editor\BlockManagement\BlockRegistration;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use WP_Error;
use WP_UnitTestCase;

class RenderTest extends WP_UnitTestCase {
	public function testRenderQueryBlockBindingsRender(): void {
		$test_data_source = HttpDataSource::from_array( [
			'service_config' => [
				'__version' => 1,
				'display_name' => 'Test ZIP API',
				'endpoint' => 'https://wpvip.com/not-a-real-api',
			],
		] );

		$test_query_runner = self::get_query_runner_with_response([
			'post code' => 12345,
			'places' => [
				[
					'place name' => 'Test City',
					'state' => 'Test State',
				],
			],
		]);

		$test_query = HttpQuery::from_array( [
			'data_source' => $test_data_source,
			'query_runner' => $test_query_runner,
			'input_schema' => [
				'zip_code' => [
					'name' => 'Zip Code',
					'type' => 'string',
				],
			],
			'output_schema' => [
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
			],
		] );

		$registration_result = register_remote_data_block( [
			'title' => 'Test ZIP API',
			'render_query' => [
				'query' => $test_query,
			],
		] );

		$this->assertTrue( $registration_result );

		// Register block configuration with WordPress, normally done during the 'init' filter
		$block_config = ConfigStore::get_block_configuration( 'remote-data-blocks/test-zip-api' );
		$this->assertTrue( is_array( $block_config ) && [] !== $block_config );
		BlockRegistration::register_block_configuration( $block_config );

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

		$dom = self::load_html_into_dom( $result_html );

		$this->assertIdInDomHasContent( $dom, 'field-zip-code', '12345' );
		$this->assertIdInDomHasContent( $dom, 'field-city', 'Test City' );
		$this->assertIdInDomHasContent( $dom, 'field-state', 'Test State' );
	}

	private function get_query_runner_with_response( array $response_data ): QueryRunner {
		return new class($response_data) extends QueryRunner {
			private $response_data;

			public function __construct( array $response_data ) {
				$this->response_data = $response_data;
			}

			protected function get_raw_response_data( HttpQueryInterface $query, array $input_variables ): array|WP_Error {
				return [
					'metadata' => [
						'age' => 100,
						'status_code' => 200,
					],
					'response_data' => $this->response_data,
				];
			}
		};
	}

	private function load_html_into_dom( string $html ): DOMDocument {
		$dom = new DOMDocument();

		try {
			$dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		} catch ( \Throwable $e ) {
			$this->fail( sprintf( 'Failed to parse rendered block as HTML: %s', $e ) );
		}

		return $dom;
	}

	private function assertIdInDomHasContent( DOMDocument $dom, string $html_id, string $expected_content ): void {
		$xpath = new DOMXPath( $dom );
		$id_nodes = $xpath->query( sprintf( "//*[@id='%s']", $html_id ) );

		$this->assertCount( 1, $id_nodes, sprintf( "Should be 1 matching node with HTML ID '%s' but %d found.", $html_id, count( $id_nodes ) ) );
		$this->assertEquals( $expected_content, $id_nodes[0]->textContent, sprintf( "Expected '%s' in node with HTML ID '%s', but found '%s' instead.", $expected_content, $html_id, $id_nodes[0]->textContent ) );
	}
}
