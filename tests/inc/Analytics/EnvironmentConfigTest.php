<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Analytics;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use RemoteDataBlocks\Analytics\EnvironmentConfig;

class EnvironmentConfigTest extends TestCase {
	public function setUp(): void {
		$GLOBALS['__wordpress_filters'] = [];
	}

	public function testGetHostingProviderReturnsWpvip(): void {
		/** @var EnvironmentConfig|MockObject */
		$mock = $this->getMockBuilder( EnvironmentConfig::class )->onlyMethods( [ 'is_wpvip_site' ] )->getMock();
		$mock->expects( $this->exactly( 1 ) )->method( 'is_wpvip_site' )->with()->willReturn( true );

		$this->assertEquals( 'wpvip', $mock->get_hosting_provider() );
	}

	public function testGetHostingProviderReturnsOther(): void {
		/** @var EnvironmentConfig|MockObject */
		$mock = $this->getMockBuilder( EnvironmentConfig::class )->onlyMethods( [ 'is_wpvip_site' ] )->getMock();
		$mock->expects( $this->exactly( 1 ) )->method( 'is_wpvip_site' )->with()->willReturn( false );

		$this->assertEquals( 'other', $mock->get_hosting_provider() );
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
}
