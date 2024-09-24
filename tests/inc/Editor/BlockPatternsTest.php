<?php

declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Editor\BlockPatterns;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Editor\BlockPatterns\BlockPatterns;

class BlockPatternsTest extends TestCase {
	public function testAddBlockArgToBindings(): void {
		$block_name    = 'test-block';
		$parsed_blocks = [
			[
				'blockName' => 'core/paragraph',
				'attrs'     => [
					'metadata' => [
						'bindings' => [
							'content' => [
								'source' => 'not-ours',
								'args'   => [
									'field' => 'content',
								],
							],
						],
					],
				],
			],
			[
				'blockName' => 'core/paragraph',
				'attrs'     => [
					'metadata' => [
						'bindings' => [
							'content' => [
								'source' => 'remote-data/binding',
								'args'   => [
									'field' => 'content',
								],
							],
						],
					],
				],
			],
			[
				'blockName' => 'core/paragraph',
				'attrs'     => [
					'content' => 'Goodbye, world!',
				],
			],
		];

		$parsed_blockss = BlockPatterns::add_block_arg_to_bindings( $block_name, $parsed_blocks );

		// The second block should be updated with the block arg.
		$this->assertSame(
			[
				'blockName' => 'core/paragraph',
				'attrs'     => [
					'metadata' => [
						'bindings' => [
							'content' => [
								'source' => 'remote-data/binding',
								'args'   => [
									'field' => 'content',
									'block' => 'test-block',
								],
							],
						],
					],
				],
			],
			$parsed_blockss[1]
		);

		// The first and third blocks should be unchanged.
		$this->assertSame( $parsed_blocks[0], $parsed_blockss[0] );
		$this->assertSame( $parsed_blocks[2], $parsed_blockss[2] );
	}
}
