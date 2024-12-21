<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Config;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Tests\Mocks\MockDataSource;

class QueryTest extends TestCase {
	private MockDataSource $data_source;
	private HttpQuery $query_context;

	protected function setUp(): void {
		$this->data_source = MockDataSource::from_array();
		$this->query_context = HttpQuery::from_array( [
			'data_source' => $this->data_source,
			'output_schema' => [ 'type' => 'null' ],
		] );
	}

	public function testGetEndpoint() {
		$result = $this->query_context->get_endpoint( [] );
		$this->assertSame( 'https://example.com/api', $result );
	}

	public function testGetImageUrl() {
		$result = $this->query_context->get_image_url();
		$this->assertNull( $result );
	}

	public function testGetRequestMethod() {
		$this->assertSame( 'GET', $this->query_context->get_request_method() );
	}

	public function testGetRequestHeaders() {
		$result = $this->query_context->get_request_headers( [] );
		$this->assertSame( [ 'Content-Type' => 'application/json' ], $result );
	}

	public function testGetRequestBody() {
		$this->assertNull( $this->query_context->get_request_body( [] ) );
	}

	public function testDefaultPreprocessResponse() {
		$raw_data = '{"key": "value"}';
		$this->assertSame( $raw_data, $this->query_context->preprocess_response( $raw_data, [] ) );
	}

	public function testCustomPreprocessResponse() {
		$custom_query_context = HttpQuery::from_array( [
			'data_source' => $this->data_source,
			'output_schema' => [ 'type' => 'string' ],
			'preprocess_response' => function ( mixed $response_data ): mixed {
				// Convert HTML to JSON
				$dom = new \DOMDocument();
				$dom->loadHTML( $response_data, LIBXML_NOERROR );
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$title = $dom->getElementsByTagName( 'title' )->item( 0 )->nodeValue;
				$paragraphs = $dom->getElementsByTagName( 'p' );
				$content = [];
				foreach ( $paragraphs as $p ) {
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$content[] = $p->nodeValue;
				}

				$data = [
					'title' => $title,
					'content' => $content,
				];

				return wp_json_encode( $data );
			},
		] );

		$html_data = '<html><head><title>Test Page</title></head><body><p>Paragraph 1</p><p>Paragraph 2</p></body></html>';
		$expected_json = '{"title":"Test Page","content":["Paragraph 1","Paragraph 2"]}';

		$this->assertSame( $expected_json, $custom_query_context->preprocess_response( $html_data, [] ) );
	}
}
