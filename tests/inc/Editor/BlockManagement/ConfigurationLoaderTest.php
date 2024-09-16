<?php

namespace RemoteDataBlocks\Tests\Editor\BlockManagement;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Editor\BlockManagement\ConfigurationLoader;
use RemoteDataBlocks\Tests\Mocks\MockDatasource;

class ConfigurationLoaderTest extends TestCase {
	public function testIsRegisteredBlockReturnsFalseWhenNoConfigurations() {
		$this->assertFalse( ConfigurationLoader::is_registered_block( 'nonexistent' ) );
	}

	public function testIsRegisteredBlockReturnsTrueForRegisteredBlock() {
		ConfigurationLoader::register_block( 'some_slick_block', new HttpQueryContext( new MockDatasource() ) );
		$this->assertTrue( ConfigurationLoader::is_registered_block( 'remote-data-blocks/some_slick_block' ) );
	}
}
