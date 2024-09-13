<?php

namespace RemoteDataBlocks\Tests\Config;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Response;
use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Tests\TestDatasource;

class QueryContextTest extends TestCase {

	private $datasource;
	private $query_context;

	protected function setUp(): void {
		$this->datasource    = new TestDatasource();
		$this->query_context = new HttpQueryContext( $this->datasource );
	}

	public function testGetEndpoint() {
		$result = $this->query_context->get_endpoint( [] );
		$this->assertEquals( 'https://example.com/api', $result );
	}

	public function testGetImageUrl() {
		$result = $this->query_context->get_image_url();
		$this->assertNull( $result );
	}

	public function testGetMetadata() {
		$mock_response = new Response( 200, [ 'Age' => '60' ] );
		$results       = [ [ 'id' => 1 ], [ 'id' => 2 ] ];

		$metadata = $this->query_context->get_metadata( $mock_response, $results );

		$this->assertArrayHasKey( 'last_updated', $metadata );
		$this->assertArrayHasKey( 'total_count', $metadata );
		$this->assertEquals( 'Last updated', $metadata['last_updated']['name'] );
		$this->assertEquals( 'string', $metadata['last_updated']['type'] );
		$this->assertEquals( 'Total count', $metadata['total_count']['name'] );
		$this->assertEquals( 'number', $metadata['total_count']['type'] );
		$this->assertEquals( 2, $metadata['total_count']['value'] );
	}

	public function testGetRequestMethod() {
		$this->assertEquals( 'GET', $this->query_context->get_request_method() );
	}

	public function testGetRequestHeaders() {
		$result = $this->query_context->get_request_headers( [] );
		$this->assertEquals( [ 'Content-Type' => 'application/json' ], $result );
	}

	public function testGetRequestBody() {
		$this->assertNull( $this->query_context->get_request_body( [] ) );
	}

	public function testGetQueryName() {
		$this->assertEquals( 'Query', $this->query_context->get_query_name() );
	}

	public function testIsResponseDataCollection() {
		$this->assertFalse( $this->query_context->is_response_data_collection() );

		$this->query_context->output_variables['is_collection'] = true;
		$this->assertTrue( $this->query_context->is_response_data_collection() );
	}

	public function testDefaultProcessResponse() {
		$raw_data = '{"key": "value"}';
		$this->assertEquals( $raw_data, $this->query_context->process_response( $raw_data, [] ) );
	}

	public function testCustomProcessResponse() {
		$custom_query_context = new class($this->datasource) extends HttpQueryContext {
			public function process_response( string $raw_response_data, array $input_variables ): string {
				// Convert HTML to JSON
				$dom = new \DOMDocument();
				$dom->loadHTML( $raw_response_data, LIBXML_NOERROR );
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$title      = $dom->getElementsByTagName( 'title' )->item( 0 )->nodeValue;
				$paragraphs = $dom->getElementsByTagName( 'p' );
				$content    = [];
				foreach ( $paragraphs as $p ) {
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$content[] = $p->nodeValue;
				}
				
				$data = [
					'title'   => $title,
					'content' => $content,
				];
				
				return wp_json_encode( $data );
			}
		};

		$html_data     = '<html><head><title>Test Page</title></head><body><p>Paragraph 1</p><p>Paragraph 2</p></body></html>';
		$expected_json = '{"title":"Test Page","content":["Paragraph 1","Paragraph 2"]}';
		
		$this->assertEquals( $expected_json, $custom_query_context->process_response( $html_data, [] ) );
	}
}
