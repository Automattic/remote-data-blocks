<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Analytics;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
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

	public function testIsWpvipSiteReturnsFalse(): void {
		$obj = new EnvironmentConfig();

		$this->assertEquals( false, $obj->is_wpvip_site() );
	}

	public function testIsWpvipSiteReturnsTrue(): void {
		/** @var MockObject|EnvironmentConfig */
		$obj = $this->getMockBuilder( EnvironmentConfig::class )->onlyMethods( [ 'get_tracks_core_properties' ] )->getMock();
		$obj->expects( $this->once() )->method( 'get_tracks_core_properties' )->with()->willReturn( [ 'hosting_provider' => 'wpvip' ] );

		$this->assertEquals( true, $obj->is_wpvip_site() );
	}

	public function testIsLocalEnvReturnsFalse(): void {
		$obj = new EnvironmentConfig();

		$this->assertEquals( false, $obj->is_local_env() );
	}

	public function testIsLocalEnvReturnsTrue(): void {
		/** @var MockObject|EnvironmentConfig */
		$obj = $this->getMockBuilder( EnvironmentConfig::class )->onlyMethods( [ 'get_tracks_core_properties' ] )->getMock();
		$obj->expects( $this->once() )->method( 'get_tracks_core_properties' )->with()->willReturn( [ 'vip_env' => 'local' ] );

		$this->assertEquals( true, $obj->is_local_env() );
	}

	public function testIsRemoteDataBlocksPluginReturnsFalse(): void {
		$obj = new EnvironmentConfig();

		$this->assertEquals( false, $obj->is_remote_data_blocks_plugin( '' ) );
	}

	public function testIsRemoteDataBlocksPluginReturnsTrue(): void {
		$obj = new EnvironmentConfig();

		$this->assertEquals( true, $obj->is_remote_data_blocks_plugin( 'remote-data-blocks/remote-data-blocks.php' ) );
	}

	public function testGetTracksCoreProperties(): void {
		/** @var MockObject|EnvironmentConfig */
		$obj = $this->getMockBuilder( EnvironmentConfig::class )->onlyMethods( [ 'get_tracks_core_properties' ] )->getMock();
		$obj->expects( $this->once() )->method( 'get_tracks_core_properties' )->with()->willReturn( [
			'hosting_provider' => 'wpvip',
			'vip_env' => 'local',
		] );

		$this->assertEquals( [
			'hosting_provider' => 'wpvip',
			'vip_env' => 'local',
		], $obj->get_tracks_core_properties() );
	}

	public function testRemoteDataBlockProperties(): void {
		$obj = new EnvironmentConfig();

		$this->assertEquals( [
			'plugin_version' => '',
		], $obj->get_remote_data_blocks_properties() );
	}
}
