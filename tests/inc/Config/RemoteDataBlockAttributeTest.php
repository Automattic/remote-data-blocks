<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Config;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\BlockAttribute\RemoteDataBlockAttribute;

class RemoteDataBlockAttributeTest extends TestCase {
	public function test_migrate_query_input(): void {
		$config = [
			'blockName' => 'test-block',
			'queryInput' => [
				'foo' => 'bar',
			],
			'metadata' => [],
		];

		$block_attribute = RemoteDataBlockAttribute::from_array( $config );
		$block_attribute = $block_attribute->to_array();

		$this->assertEquals( 1, count( $block_attribute['queryInputs'] ) );
		$this->assertEquals( [ 'foo' => 'bar' ], $block_attribute['queryInputs'][0] );
	}

	public function test_migrate_query_input_does_not_override_query_inputs(): void {
		$config = [
			'blockName' => 'test-block',
			'queryInput' => [
				'foo' => 'bar',
			],
			'queryInputs' => [
				[
					'foo' => 'baz not bar',
				],
			],
			'metadata' => [],
		];

		$block_attribute = RemoteDataBlockAttribute::from_array( $config );
		$block_attribute = $block_attribute->to_array();

		$this->assertEquals( 1, count( $block_attribute['queryInputs'] ) );
		$this->assertEquals( [ 'foo' => 'baz not bar' ], $block_attribute['queryInputs'][0] );
	}

	public function test_migrate_results(): void {
		$config = [
			'blockName' => 'test-block',
			'results' => [
				[
					'foo' => 'bar',
					'fizz' => 'buzz',
				],
				[
					'foo' => 'barbar',
					'fizz' => 'buzzbuzz',
				],
			],
		];

		$block_attribute = RemoteDataBlockAttribute::from_array( $config );
		$block_attribute = $block_attribute->to_array();

		$this->assertEquals( 2, count( $block_attribute['results'] ) );

		$this->assertMatchesRegularExpression( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $block_attribute['results'][0]['uuid'] );
		$this->assertEquals( [ 'foo', 'fizz' ], array_keys( $block_attribute['results'][0]['result'] ) );
		$this->assertEquals( [
			'name' => 'foo',
			'type' => 'unknown',
			'value' => 'bar',
		], $block_attribute['results'][0]['result']['foo'] );
		$this->assertEquals( [
			'name' => 'fizz',
			'type' => 'unknown',
			'value' => 'buzz',
		], $block_attribute['results'][0]['result']['fizz'] );

		$this->assertMatchesRegularExpression( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $block_attribute['results'][1]['uuid'] );
		$this->assertEquals( [ 'foo', 'fizz' ], array_keys( $block_attribute['results'][1]['result'] ) );
		$this->assertEquals( [
			'name' => 'foo',
			'type' => 'unknown',
			'value' => 'barbar',
		], $block_attribute['results'][1]['result']['foo'] );
		$this->assertEquals( [
			'name' => 'fizz',
			'type' => 'unknown',
			'value' => 'buzzbuzz',
		], $block_attribute['results'][1]['result']['fizz'] );
	}

	public function test_migrate_results_does_not_override_valid_results(): void {
		$config = [
			'blockName' => 'test-block',
			'results' => [
				[
					'uuid' => '00000000-0000-0000-0000-000000000000',
					'result' => [
						'foo' => [
							'name' => 'foo',
							'type' => 'string',
							'value' => 'bar',
						],
						'fizz' => [
							'name' => 'fizz',
							'type' => 'string',
							'value' => 'buzz',
						],
					],
				],
			],
		];

		$block_attribute = RemoteDataBlockAttribute::from_array( $config );
		$block_attribute = $block_attribute->to_array();

		$this->assertEquals( 1, count( $block_attribute['results'] ) );

		// These would be overwritten if the migration was applied.
		$this->assertEquals( '00000000-0000-0000-0000-000000000000', $block_attribute['results'][0]['uuid'] );
		$this->assertEquals( 'string', $block_attribute['results'][0]['result']['foo']['type'] );
	}

	public function test_block_name_is_required(): void {
		$config = [];

		$block_attribute = RemoteDataBlockAttribute::from_array( $config );

		$this->assertTrue( is_wp_error( $block_attribute ) );
		$this->assertEquals( 'Object must have valid property: blockName', $block_attribute->get_error_message() );
	}

	public function test_only_block_name_is_required(): void {
		$config = [
			'blockName' => 'test-block',
		];

		$block_attribute = RemoteDataBlockAttribute::from_array( $config );
		$block_attribute = $block_attribute->to_array();

		$this->assertEquals( 'test-block', $block_attribute['blockName'] );
	}
}
