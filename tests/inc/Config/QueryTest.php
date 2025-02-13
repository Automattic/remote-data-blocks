<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Config;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Tests\Mocks\MockDataSource;
use RemoteDataBlocks\Tests\Mocks\MockQuery;
use RemoteDataBlocks\Tests\Mocks\MockQueryRunner;

class QueryTest extends TestCase {
	private MockDataSource $data_source;
	private HttpQuery $query_context;

	protected function setUp(): void {
		$this->data_source = MockDataSource::create();
		$this->query_context = HttpQuery::from_array( [
			'data_source' => $this->data_source,
			'output_schema' => [ 'type' => 'null' ],
		] );
	}

	public function testGetEndpoint(): void {
		$result = $this->query_context->get_endpoint( [] );
		$this->assertSame( 'https://example.com/api', $result );
	}

	public function testGetImageUrl(): void {
		$result = $this->query_context->get_image_url();
		$this->assertNull( $result );
	}

	public function testGetRequestMethod(): void {
		$this->assertSame( 'GET', $this->query_context->get_request_method() );
	}

	public function testGetRequestHeaders(): void {
		$result = $this->query_context->get_request_headers( [] );
		$this->assertSame( [ 'Content-Type' => 'application/json' ], $result );
	}

	public function testGetRequestBody(): void {
		$this->assertNull( $this->query_context->get_request_body( [] ) );
	}

	public function testDefaultPreprocessResponse(): void {
		$raw_data = '{"key": "value"}';
		$this->assertSame( $raw_data, $this->query_context->preprocess_response( $raw_data, [] ) );
	}

	public function testCustomPreprocessResponse(): void {
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

	public function testQueryAsDataSource(): void {
		$mock_qr = new MockQueryRunner();
		$mock_qr->addResult( 'foo', 'bar' );

		$query_with_query_as_data_source = HttpQuery::from_array( [
			'data_source' => MockQuery::create( [ 'query_runner' => $mock_qr ] ),
			'output_schema' => [
				'type' => [
					'nested_foo' => [
						'path' => '$.results[0].result.foo.value',
						'type' => 'string',
					],
				],
			],
		] );

		$result = $query_with_query_as_data_source->execute( [] )['results'][0]['result']['nested_foo'];
		$this->assertSame( 'bar', $result['value'] );
	}
}
