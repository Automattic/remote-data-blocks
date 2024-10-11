<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Config;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Tests\Mocks\MockDataSource;
use RemoteDataBlocks\Tests\Mocks\MockValidator;

class QueryContextTest extends TestCase {
	private MockDataSource $data_source;
	private HttpQueryContext $query_context;

	protected function setUp(): void {
		$this->data_source = MockDataSource::from_array( MockDataSource::MOCK_CONFIG, new MockValidator() );
		$this->query_context = new HttpQueryContext( $this->data_source );
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

	public function testGetQueryName() {
		$this->assertSame( 'Query', $this->query_context->get_query_name() );
	}

	public function testIsResponseDataCollection() {
		$this->assertFalse( $this->query_context->is_response_data_collection() );

		$this->query_context->output_schema['is_collection'] = true;
		$this->assertTrue( $this->query_context->is_response_data_collection() );
	}

	public function testDefaultProcessResponse() {
		$raw_data = '{"key": "value"}';
		$this->assertSame( $raw_data, $this->query_context->process_response( $raw_data, [] ) );
	}

	public function testCustomProcessResponse() {
		$custom_query_context = new class($this->data_source) extends HttpQueryContext {
			public function process_response( string $raw_response_data, array $input_variables ): string {
				// Convert HTML to JSON
				$dom = new \DOMDocument();
				$dom->loadHTML( $raw_response_data, LIBXML_NOERROR );
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
			}
		};

		$html_data = '<html><head><title>Test Page</title></head><body><p>Paragraph 1</p><p>Paragraph 2</p></body></html>';
		$expected_json = '{"title":"Test Page","content":["Paragraph 1","Paragraph 2"]}';
		
		$this->assertSame( $expected_json, $custom_query_context->process_response( $html_data, [] ) );
	}
}
