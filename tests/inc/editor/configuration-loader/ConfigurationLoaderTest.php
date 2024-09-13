<?php

namespace RemoteDataBlocks\Editor;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\HttpQueryContext;
use RemoteDataBlocks\Test\TestDatasource;

class ConfigurationLoaderTest extends TestCase {
	public function testIsRegisteredBlockReturnsFalseWhenNoConfigurations() {
		$this->assertFalse( ConfigurationLoader::is_registered_block( 'nonexistent' ) );
	}

	public function testIsRegisteredBlockReturnsTrueForRegisteredBlock() {
		ConfigurationLoader::register_block( 'some_slick_block', new HttpQueryContext( new TestDatasource() ) );
		$this->assertTrue( ConfigurationLoader::is_registered_block( 'remote-data-blocks/some_slick_block' ) );
	}
}
