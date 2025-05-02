<?php declare(strict_types = 1);

use WP_UnitTestCase;
use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use RemoteDataBlocks\Editor\BlockManagement\BlockRegistration;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\Integrations\GenericHttp\GenericHttpDataSource;

class RDBTestCase extends WP_UnitTestCase {
	// Query mocking

	protected function register_mocked_data_block( string $block_title, array $api_response, array $output_schema ): void {
		$test_query_runner = $this->get_query_runner_with_response( $api_response );

		$test_data_source = HttpDataSource::from_array( [
			'__version' => 1,
			'display_name' => 'Test API',

			// Mocked query runner will not actually make a request to the endpoint URL.
			'endpoint' => 'https://example.com/not-a-real-api',
		] );

		$test_query = HttpQuery::from_array( [
			'data_source' => $test_data_source,
			'query_runner' => $test_query_runner,
			'output_schema' => $output_schema,
		] );

		$registration_result = register_remote_data_block( [
			'title' => $block_title,
			'render_query' => [
				'query' => $test_query,
			],
		] );

		$this->assertTrue( $registration_result );
		$this->register_remote_data_block_from_block_title( $block_title );
	}

	protected function register_failed_query_data_block( string $block_title ): void {
		$test_query_runner = $this->get_query_runner_with_response( [], 500 );

		$test_data_source = GenericHttpDataSource::from_array( [
			'service_config' => [
				'__version' => 1,
				'display_name' => 'Test Failing API',

				// Mocked query runner will not actually make a request to the endpoint URL.
				'endpoint' => 'https://example.com/failed-api',
			],
		] );

		$test_query = HttpQuery::from_array( [
			'data_source' => $test_data_source,
			'query_runner' => $test_query_runner,
			'output_schema' => [
				'is_collection' => false,
				'type' => [
					'title' => [
						'name' => 'Title',
						'path' => '$.title',
						'type' => 'string',
					],
					'content' => [
						'name' => 'Content',
						'path' => '$.content',
						'type' => 'html',
					],
				],
			],
		] );

		$registration_result = register_remote_data_block( [
			'title' => $block_title,
			'render_query' => [
				'query' => $test_query,
			],
		] );

		$this->assertTrue( $registration_result );
		$this->register_remote_data_block_from_block_title( $block_title );
	}

	/**
	 * Register block configuration with WordPress, normally done during the 'init' filter
	 *
	 * @param string $block_title The block title.
	 */
	protected function register_remote_data_block_from_block_title( string $block_title ): void {
		$block_config = ConfigStore::get_block_configuration( ConfigStore::get_block_name( $block_title ) );
		$this->assertTrue( is_array( $block_config ) && [] !== $block_config );
		BlockRegistration::register_block_configuration( $block_config );
	}

	protected function get_query_runner_with_response( array $response_data, int $status_code = 200 ): QueryRunner {
		return new class($response_data, $status_code) extends QueryRunner {
			private $response_data;
			private $status_code;

			public function __construct( array $response_data, int $status_code ) {
				parent::__construct( null, [] );

				$this->response_data = $response_data;
				$this->status_code = $status_code;
			}

			protected function get_raw_response_data( array $request_details, array $input_variables ): array|WP_Error {
				return [
					'metadata' => [
						'age' => 100,
						'status_code' => $this->status_code,
					],
					'response_data' => $this->response_data,
				];
			}
		};
	}

	// DOM testing

	protected function load_html( string $html ): DOMDocument {
		$dom = new DOMDocument();

		try {
			$dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		} catch ( \Throwable $e ) {
			$this->fail( sprintf( 'Failed to parse HTML: %s', $e ) );
		}

		return $dom;
	}

	protected function get_dom_element_by_html_id( DOMDocument $dom, string $html_id ): DOMNodeList|false {
		$xpath = new DOMXPath( $dom );
		$nodes = $xpath->query( sprintf( "//*[@id='%s']", $html_id ) );

		return $nodes;
	}

	protected function get_dom_elements_by_html_class( DOMDocument $dom, string $html_class ): DOMNodeList|false {
		$xpath = new DOMXPath( $dom );
		$nodes = $xpath->query( sprintf( "//*[@class='%s']", $html_class ) );

		return $nodes;
	}

	protected function assertDomIdHasTextContent( DOMDocument $dom, string $html_id, string $expected_content ): void {
		$id_nodes = $this->get_dom_element_by_html_id( $dom, $html_id );

		$this->assertCount( 1, $id_nodes, sprintf( "Should be 1 matching node with HTML ID '%s' but %d found.", $html_id, count( $id_nodes ) ) );
		$this->assertEquals( $expected_content, $id_nodes[0]->textContent, sprintf( "Expected '%s' in node with HTML ID '%s', but found '%s' instead.", $expected_content, $html_id, $id_nodes[0]->textContent ) );
	}

	protected function assertDomIdHasHtmlContent( DOMDocument $dom, string $html_id, string $expected_content ): void {
		$id_nodes = $this->get_dom_element_by_html_id( $dom, $html_id );

		$this->assertCount( 1, $id_nodes, sprintf( "Should be 1 matching node with HTML ID '%s' but %d found.", $html_id, count( $id_nodes ) ) );

		// DOM nodes don't have an innerHTML, so build one from child nodes
		$inner_html_content = array_reduce(
			iterator_to_array( $id_nodes[0]->childNodes ),
			function ( $carry, DOMNode $child ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- This is a built-in PHP function
				return $carry . $child->ownerDocument->saveHTML( $child );
			}
		);

		$this->assertEquals( $expected_content, $inner_html_content, sprintf( "Expected '%s' in node with HTML ID '%s', but found '%s' instead.", $expected_content, $html_id, $id_nodes[0]->textContent ) );
	}
}
