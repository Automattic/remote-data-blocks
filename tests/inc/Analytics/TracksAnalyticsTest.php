<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Analytics;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Analytics\TracksAnalytics;

// Define a dummy class if it doesn't exist
if ( ! class_exists( 'Automattic\VIP\Telemetry\Tracks' ) ) {
	class MockTracks {
		public function __construct( $prefix, $global_props ) {}

		public function record_event( $name, $props ) {}
	}
}

class TracksAnalyticsTest extends TestCase {
	public function testInitDoesNotSetTracksIfLibraryIsNotPresent(): void {
		$obj = new TracksAnalytics();

		$this->assertEquals( null, $obj->get_instance() );
	}

	public function testInitDoesNotSetTracksIfTrackingIsNotEnabled(): void {
		/**
		 * @var TracksAnalytics|MockObject
		 */
		$mock = $this->getMockBuilder( TracksAnalytics::class )->disableOriginalConstructor()->onlyMethods( [ 'have_tracks_library' ] )->getMock();
		$mock->method( 'have_tracks_library' )->willReturn( true );

		$mock->__construct();

		$this->assertEquals( true, $mock->have_tracks_library() );
		$this->assertEquals( null, $mock->get_instance() );
	}

	public function testInitDoesSetTracksIfTrackingIsEnabled(): void {
		/**
		 * @var TracksAnalytics|MockObject
		 */
		$mock = $this->getMockBuilder( TracksAnalytics::class )->disableOriginalConstructor()->onlyMethods( [ 'have_tracks_library', 'is_enabled_via_filter', 'get_tracks_library' ] )->getMock();
		$mock->method( 'have_tracks_library' )->willReturn( true );
		$mock->method( 'is_enabled_via_filter' )->willReturn( true );
		$mock->method( 'get_tracks_library' )->willReturn( MockTracks::class );

		$mock->__construct();

		$this->assertEquals( true, $mock->have_tracks_library() );
		$this->assertEquals( true, $mock->is_enabled_via_filter() );
		$this->assertEquals( null, $mock->get_instance() );
	}
}
