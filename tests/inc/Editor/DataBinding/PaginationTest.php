<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Editor\DataBinding;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Editor\DataBinding\Pagination;

class PaginationTest extends TestCase {
	public function test_decode_query_var(): void {
		$query_var_value = [
			'test-block' => [
				'page' => 2,
				'per_page' => 10,
			],
		];

		$encoded_query_var = base64_encode( wp_json_encode( $query_var_value ) );

		$reflection = new \ReflectionClass( Pagination::class );
		$method = $reflection->getMethod( 'decode_query_var' );

		$decoded_query_var = $method->invoke( null, $encoded_query_var );

		$this->assertEquals( $query_var_value, $decoded_query_var );
	}

	public function test_encode_query_var(): void {
		$query_var_value = [
			'test-block' => [
				'page' => 2,
				'per_page' => 10,
			],
		];

		$reflection = new \ReflectionClass( Pagination::class );
		$method = $reflection->getMethod( 'encode_query_var' );

		$encoded_query_var = $method->invoke( null, $query_var_value );
		$decoded_query_var = json_decode( base64_decode( $encoded_query_var ), true );

		$this->assertEquals( $query_var_value, $decoded_query_var );
	}

	public function test_format_pagination_data_for_offset_pagination(): void {
		$pagination_data = [
			'total_items' => 100,
		];

		$query_input_schema = [
			'offset' => [ 'type' => 'ui:pagination_offset' ],
			'per_page' => [ 'type' => 'ui:pagination_per_page' ],
		];

		$input_variables = [
			'offset' => 20,
			'per_page' => 10,
		];

		$result = Pagination::format_pagination_data_for_query_response( $pagination_data, $query_input_schema, $input_variables );

		$this->assertEquals( 'OFFSET', $result['type'] );
		$this->assertEquals( 10, $result['per_page'] );
		$this->assertEquals( 100, $result['total_items'] );
		$this->assertArrayHasKey( 'next_page', $result['input_variables'] );
		$this->assertArrayHasKey( 'previous_page', $result['input_variables'] );
		$this->assertEquals( 30, $result['input_variables']['next_page']['offset'] );
		$this->assertEquals( 10, $result['input_variables']['previous_page']['offset'] );
	}

	public function test_format_pagination_data_for_page_pagination(): void {
		$pagination_data = [
			'total_items' => 50,
		];

		$query_input_schema = [
			'page' => [ 'type' => 'ui:pagination_page' ],
			'per_page' => [ 'type' => 'ui:pagination_per_page' ],
		];

		$input_variables = [
			'page' => 2,
			'per_page' => 10,
		];

		$result = Pagination::format_pagination_data_for_query_response( $pagination_data, $query_input_schema, $input_variables );

		$this->assertEquals( 'PAGE', $result['type'] );
		$this->assertEquals( 10, $result['per_page'] );
		$this->assertEquals( 50, $result['total_items'] );
		$this->assertArrayHasKey( 'next_page', $result['input_variables'] );
		$this->assertArrayHasKey( 'previous_page', $result['input_variables'] );
		$this->assertEquals( 3, $result['input_variables']['next_page']['page'] );
		$this->assertEquals( 1, $result['input_variables']['previous_page']['page'] );
	}

	public function test_format_pagination_data_for_cursor_pagination(): void {
		$pagination_data = [
			'total_items' => 200,
			'cursor_next' => 'next-cursor',
			'cursor_previous' => 'previous-cursor',
		];

		$query_input_schema = [
			'cursor_next' => [ 'type' => 'ui:pagination_cursor_next' ],
			'cursor_previous' => [ 'type' => 'ui:pagination_cursor_previous' ],
			'per_page' => [ 'type' => 'ui:pagination_per_page' ],
		];

		$input_variables = [
			'per_page' => 20,
		];

		$result = Pagination::format_pagination_data_for_query_response( $pagination_data, $query_input_schema, $input_variables );

		$this->assertEquals( 'CURSOR', $result['type'] );
		$this->assertEquals( 20, $result['per_page'] );
		$this->assertEquals( 200, $result['total_items'] );
		$this->assertArrayHasKey( 'next_page', $result['input_variables'] );
		$this->assertArrayHasKey( 'previous_page', $result['input_variables'] );
		$this->assertEquals( 'next-cursor', $result['input_variables']['next_page']['cursor_next'] );
		$this->assertEquals( 'previous-cursor', $result['input_variables']['previous_page']['cursor_previous'] );
	}

	public function test_format_pagination_data_for_no_pagination(): void {
		$pagination_data = [
			'total_items' => 0,
		];

		$query_input_schema = [];

		$input_variables = [];

		$result = Pagination::format_pagination_data_for_query_response( $pagination_data, $query_input_schema, $input_variables );

		$this->assertEquals( [], $result );
	}
}
