<?php declare(strict_types = 1);

namespace RemoteDataBlocks\IntegrationTests\Blocks;

use RDBTestCase;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;

class BlockConfigTest extends RDBTestCase {
	public function testBlockWithComplexName(): void {
		$block_name = ConfigStore::get_block_name( 'Block Name with/slashes and*asterisks' );
		$this->assertEquals( 'remote-data-blocks/block-name-with-slashes-andasterisks', $block_name );
	}
}
