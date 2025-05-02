<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Config\QueryRunner;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\QueryRunner\QueryResponseParser;

/**
 * QueryResponseParserTest class
 */
final class QueryResponseParserTest extends TestCase {
	private QueryResponseParser $parser;

	protected function setUp(): void {
		parent::setUp();
		$this->parser = new QueryResponseParser();
	}

	public function test_json_path_expressions(): void {
		$data = [
			'users' => [
				[
					'name' => 'Alice',
					'age' => 30,
				],
				[
					'name' => 'Bob',
					'age' => 25,
				],
			],
		];

		$schema = [
			'path' => '$.users[*]',
			'is_collection' => true,
			'type' => [
				'userName' => [
					'path' => '$.name',
					'type' => 'string',
				],
				'userAge' => [
					'path' => '$.age',
					'type' => 'integer',
				],
			],
		];

		$result = $this->parser->parse( $data, $schema );

		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
		$this->assertArrayHasKey( 'result', $result[0] );
		$this->assertArrayHasKey( 'uuid', $result[0] );
		$this->assertEquals( 'Alice', $result[0]['result']['userName']['value'] );
		$this->assertEquals( 30, $result[0]['result']['userAge']['value'] );
		$this->assertEquals( 'Bob', $result[1]['result']['userName']['value'] );
		$this->assertEquals( 25, $result[1]['result']['userAge']['value'] );
	}

	public function test_generate_callback(): void {
		$data = [
			[
				'firstName' => 'Charlie',
				'lastName' => 'Brown',
			],
		];

		$schema = [
			'is_collection' => true,
			'type' => [
				'fullName' => [
					'type' => 'string',
					'generate' => function ( $item ) {
						return $item['firstName'] . ' ' . $item['lastName'];
					},
				],
			],
		];

		$result = $this->parser->parse( $data, $schema );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'result', $result[0] );
		$this->assertArrayHasKey( 'uuid', $result[0] );
		$this->assertEquals( 'Charlie Brown', $result[0]['result']['fullName']['value'] );
	}

	public function test_format_callback(): void {
		$data = [
			[ 'price' => 1234.56 ],
		];

		$schema = [
			'is_collection' => true,
			'type' => [
				'formattedPrice' => [
					'path' => '$.price',
					'type' => 'float',
					'format' => function ( $value ) {
						return '$' . number_format( $value, 2 );
					},
				],
			],
		];

		$result = $this->parser->parse( $data, $schema );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'result', $result[0] );
		$this->assertArrayHasKey( 'uuid', $result[0] );
		$this->assertEquals( '$1,234.56', $result[0]['result']['formattedPrice']['value'] );
	}

	public function test_non_collection_result(): void {
		$data = [
			'product' => [
				'name' => 'Gadget',
				'price' => 99.99,
			],
		];

		$schema = [
			'path' => '$.product',
			'is_collection' => false, // Explicitly false
			'type' => [
				'productName' => [
					'path' => '$.name',
					'type' => 'string',
				],
				'productPrice' => [
					'path' => '$.price',
					'type' => 'float',
				],
			],
		];

		$result = $this->parser->parse( $data, $schema );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'result', $result );
		$this->assertArrayHasKey( 'uuid', $result );
		$this->assertEquals( 'Gadget', $result['result']['productName']['value'] );
		$this->assertEquals( 99.99, $result['result']['productPrice']['value'] );
	}

	public function test_primitive_type_parsing(): void {
		$data = [ 'ids' => [ 101, 102, 103 ] ];

		$schema = [
			'path' => '$.ids[*]',
			'is_collection' => true,
			'type' => 'integer',
		];

		$result = $this->parser->parse( $data, $schema );

		$this->assertIsArray( $result );
		$this->assertEquals( [ 101, 102, 103 ], $result );
	}

	public function test_primitive_type_parsing_single_value(): void {
		$data = [ 'status' => 'active' ];

		$schema = [
			'path' => '$.status',
			'is_collection' => false,
			'type' => 'string',
		];

		$result = $this->parser->parse( $data, $schema );

		$this->assertEquals( 'active', $result );
	}

	public function test_default_value(): void {
		$data = [
			'items' => [
				[ 'name' => 'Apple' ],
				[
					'name' => 'Banana',
					'color' => 'Yellow',
				],
			],
		];

		$schema = [
			'path' => '$.items[*]',
			'is_collection' => true,
			'type' => [
				'itemName' => [
					'path' => '$.name',
					'type' => 'string',
				],
				'itemColor' => [
					'path' => '$.color',
					'type' => 'string',
					'default_value' => 'Unknown',
				],
			],
		];

		$result = $this->parser->parse( $data, $schema );

		$this->assertCount( 2, $result );
		$this->assertEquals( 'Unknown', $result[0]['result']['itemColor']['value'] );
		$this->assertEquals( 'Yellow', $result[1]['result']['itemColor']['value'] );
	}

	public function test_nested_objects(): void {
		$data = [
			'orders' => [
				[
					'id' => 1,
					'customer' => [
						'name' => 'David',
						'email' => 'david@example.com',
					],
				],
			],
		];

		$schema = [
			'path' => '$.orders[*]',
			'is_collection' => true,
			'type' => [
				'orderId' => [
					'path' => '$.id',
					'type' => 'integer',
				],
				'customerInfo' => [
					'path' => '$.customer',
					'type' => [
						'customerName' => [
							'path' => '$.name',
							'type' => 'string',
						],
						'customerEmail' => [
							'path' => '$.email',
							'type' => 'string',
						],
					],
				],
			],
		];

		$result = $this->parser->parse( $data, $schema );

		$this->assertCount( 1, $result );

		$customer_info = $result[0]['result']['customerInfo']['value'];

		$this->assertIsArray( $customer_info );
		$this->assertArrayHasKey( 'result', $customer_info );
		$this->assertEquals( 'David', $customer_info['result']['customerName']['value'] );
		$this->assertEquals( 'david@example.com', $customer_info['result']['customerEmail']['value'] );
	}
}
