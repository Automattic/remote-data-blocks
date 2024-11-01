<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Analytics;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Analytics\EnvironmentConfig;

class EnvironmentConfigTest extends TestCase {
	public function setUp(): void {
		$GLOBALS['__wordpress_filters'] = [];
	}

	public function testIsEnabledViaFilterReturnsFalse(): void {
		$obj = new EnvironmentConfig();

		$this->assertEquals( false, $obj->is_enabled_via_filter() );
	}

	public function testIsEnabledViaFilterReturnsTrue(): void {
		$GLOBALS['__wordpress_filters']['remote_data_blocks_enable_tracks_analytics'] = true;
		$obj = new EnvironmentConfig();

		$this->assertEquals( true, $obj->is_enabled_via_filter() );
	}

	public function testIsRemoteDataBlocksPluginReturnsFalse(): void {
		$obj = new EnvironmentConfig();

		$this->assertEquals( false, $obj->is_remote_data_blocks_plugin( '' ) );
	}

	public function testIsRemoteDataBlocksPluginReturnsTrue(): void {
		$obj = new EnvironmentConfig();

		$this->assertEquals( true, $obj->is_remote_data_blocks_plugin( 'remote-data-blocks/remote-data-blocks.php' ) );
	}
}
