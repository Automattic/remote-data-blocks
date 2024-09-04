<?php

namespace RemoteDataBlocks\Editor;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\QueryContext;
use RemoteDataBlocks\Config\HttpDatasource;

class ConfigurationLoaderTest extends TestCase {
	public function testIsRegisteredBlockReturnsFalseWhenNoConfigurations() {
		$this->assertFalse( ConfigurationLoader::is_registered_block( 'nonexistent' ) );
	}

	public function testIsRegisteredBlockReturnsTrueForRegisteredBlock() {
		$http_datasource = new HttpDatasource( [
			'endpoint' => 'https://api.example.com',
			'headers' => [ 'Content-Type' => 'application/json' ],
		] );
		ConfigurationLoader::register_block( 'some_slick_block', new QueryContext( $http_datasource ) );
		$this->assertTrue( ConfigurationLoader::is_registered_block( 'remote-data-blocks/some_slick_block' ) );
	}
}
