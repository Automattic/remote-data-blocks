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
