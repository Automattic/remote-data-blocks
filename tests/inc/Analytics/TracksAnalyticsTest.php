<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Analytics;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Analytics\TracksAnalytics;
use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\ExampleApi\Queries\ExampleApiDataSource;
use RemoteDataBlocks\Integrations\Shopify\ShopifyDataSource;

// Define a mock class for Tracks.
if ( ! class_exists( 'Automattic\VIP\Telemetry\Tracks' ) ) {
	class MockTracks {
		public function record_event( $_name, $_props ) {}
	}
}

class TracksAnalyticsTest extends TestCase {
	public function testInitDoesNotSetTracksIfLibraryIsNotPresent(): void {
		$obj = new TracksAnalytics();

		$this->assertEquals( null, $obj->get_instance() );
	}

	public function testInitDoesSetTracksIfTrackingIsEnabledViaFilter(): void {
		/** @var TracksAnalytics|MockObject */
		$mock = $this->getMockBuilder( TracksAnalytics::class )->disableOriginalConstructor()->onlyMethods( [ 'get_tracks_lib_class', 'is_enabled_via_filter', 'setup_tracking_via_hooks' ] )->getMock();
		$mock->method( 'get_tracks_lib_class' )->willReturn( MockTracks::class );
		$mock->method( 'is_enabled_via_filter' )->willReturn( true );
		$mock->expects( $this->once() )->method( 'setup_tracking_via_hooks' )->with();

		$mock->__construct();

		$this->assertInstanceOf( MockTracks::class, $mock->get_instance() );
	}

	public function testInitDoesSetTracksIfTrackingIsEnabledOnVipSite(): void {
		/** @var TracksAnalytics|MockObject */
		$mock = $this->getMockBuilder( TracksAnalytics::class )->disableOriginalConstructor()->onlyMethods( [ 'get_tracks_lib_class', 'is_wpvip_site', 'setup_tracking_via_hooks' ] )->getMock();
		$mock->method( 'get_tracks_lib_class' )->willReturn( MockTracks::class );
		$mock->method( 'is_wpvip_site' )->willReturn( true );
		$mock->expects( $this->exactly( 1 ) )->method( 'setup_tracking_via_hooks' )->with();

		$mock->__construct();

		$this->assertInstanceOf( MockTracks::class, $mock->get_instance() );
	}

	public function testGetHostingProviderReturnsWpvip(): void {
		/** @var TracksAnalytics|MockObject */
		$mock = $this->getMockBuilder( TracksAnalytics::class )->disableOriginalConstructor()->onlyMethods( [ 'is_wpvip_site' ] )->getMock();
		$mock->method( 'is_wpvip_site' )->willReturn( true );

		$method = get_private_method( TracksAnalytics::class, 'get_hosting_provider' );
		$result = $method->invoke( $mock );

		$this->assertEquals( 'wpvip', $result );
	}

	public function testGetHostingProviderReturnsOther(): void {
		$obj = new TracksAnalytics();

		$method = get_private_method( TracksAnalytics::class, 'get_hosting_provider' );
		$result = $method->invoke( $obj );

		$this->assertEquals( 'other', $result );
	}

	public function testTrackPluginActivationDoesNotRecordEventIfPluginIsNotRDB(): void {
		/** @var TracksAnalytics|MockObject */
		$mock = $this->getMockBuilder( TracksAnalytics::class )->onlyMethods( [ 'is_remote_data_blocks_plugin', 'record_event' ] )->getMock();
		$mock->method( 'is_remote_data_blocks_plugin' )->with( 'plugin_path' )->willReturn( false );

		$mock->expects( $this->exactly( 0 ) )->method( 'record_event' );
		$mock->track_plugin_activation( 'plugin_path' );
	}

	public function testTrackPluginActivationDoesRecordEventIfPluginIsRDB(): void {
		/** @var TracksAnalytics|MockObject */
		$mock = $this->getMockBuilder( TracksAnalytics::class )->onlyMethods( [ 'is_remote_data_blocks_plugin', 'record_event' ] )->getMock();
		$mock->method( 'is_remote_data_blocks_plugin' )->with( 'plugin_path' )->willReturn( true );

		$mock->expects( $this->exactly( 1 ) )->method( 'record_event' )->with( 'remotedatablocks_plugin_toggle', [ 'action' => 'activate' ] );
		$mock->track_plugin_activation( 'plugin_path' );
	}

	public function testTrackPluginDeactivationDoesNotRecordEventIfPluginIsNotRDB(): void {
		/** @var TracksAnalytics|MockObject */
		$mock = $this->getMockBuilder( TracksAnalytics::class )->onlyMethods( [ 'is_remote_data_blocks_plugin', 'record_event' ] )->getMock();
		$mock->method( 'is_remote_data_blocks_plugin' )->with( 'plugin_path' )->willReturn( false );

		$mock->expects( $this->exactly( 0 ) )->method( 'record_event' );
		$mock->track_plugin_deactivation( 'plugin_path' );
	}

	public function testTrackPluginDeactivationDoesRecordEventIfPluginIsRDB(): void {
		/** @var TracksAnalytics|MockObject */
		$mock = $this->getMockBuilder( TracksAnalytics::class )->onlyMethods( [ 'is_remote_data_blocks_plugin', 'record_event' ] )->getMock();
		$mock->method( 'is_remote_data_blocks_plugin' )->with( 'plugin_path' )->willReturn( true );

		$mock->expects( $this->exactly( 1 ) )->method( 'record_event' )->with( 'remotedatablocks_plugin_toggle', [ 'action' => 'deactivate' ] );
		$mock->track_plugin_deactivation( 'plugin_path' );
	}

	public function testTrackRemoteDataBlocksUsageDoesNotTrackEventIfUsageShouldNotBeTracked(): void {
		$mock = $this->getMockBuilder( TracksAnalytics::class )->onlyMethods( [ 'should_track_blocks_usage', 'record_event' ] )->getMock();
		$mock->method( 'should_track_blocks_usage' )->willReturn( false );

		$mock->expects( $this->exactly( 0 ) )->method( 'record_event' );
		$mock->track_remote_data_blocks_usage( 1, (object) [] );
	}

	public function testTrackRemoteDataBlocksUsageDoesNotTrackEventIfPostStatusIsNotPublish(): void {
		$mock = $this->getMockBuilder( TracksAnalytics::class )->onlyMethods( [ 'should_track_blocks_usage', 'record_event' ] )->getMock();
		$mock->method( 'should_track_blocks_usage' )->willReturn( true );

		$mock->expects( $this->exactly( 0 ) )->method( 'record_event' );
		$mock->track_remote_data_blocks_usage( 1, (object) [ 'post_status' => 'draft' ] );
	}

	public function testTrackRemoteDataBlocksUsageDoesNotTrackEventIfPostContentHaveNoRemoteBlocks(): void {
		$mock = $this->getMockBuilder( TracksAnalytics::class )->onlyMethods( [ 'should_track_blocks_usage', 'record_event' ] )->getMock();
		$mock->method( 'should_track_blocks_usage' )->willReturn( true );
		// Setup data sources.
		ConfigStore::init();
		ConfigStore::set_configuration( 'remote-data-blocks/shopify-vip-store', [
			'queries' => [ new HttpQueryContext( ShopifyDataSource::create( 'access_token', 'name' ) ) ],
		] );
		ConfigStore::set_configuration( 'remote-data-blocks/conference-event', [
			'queries' => [
				new HttpQueryContext( ExampleApiDataSource::from_array( [
					'slug'    => 'example-api',
					'service' => 'example_api',
				] ) ),
			],
		] );

		$mock->expects( $this->exactly( 1 ) )->method( 'record_event' )->with( 'remotedatablocks_blocks_usage_stats', [
			'post_status'                    => 'publish',
			'post_type'                      => 'post',
			'shopify_data_source_count'      => 2,
			'remote_data_blocks_total_count' => 3,
			'example_api_data_source_count'  => 1,
		] );
		$mock->track_remote_data_blocks_usage( 1, (object) [
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_content' => '<!-- wp:remote-data-blocks/shopify-vip-store {"remoteData":{"blockName":"remote-data-blocks/shopify-vip-store","isCollection":false,"metadata":{"last_updated":{"name":"Last updated","type":"string","value":"2024-10-07 14:43:51"},"total_count":{"name":"Total count","type":"number","value":1}},"queryInput":{"id":"gid://shopify/Product/8642689958112"},"resultId":"d9583b2d-b79f-4af7-8adc-a723c53d7f67","results":[{"description":"Our floating shelf is the perfect way to store all your Tonal accessories. Its sleek, versatile design makes this an easy fit with any style room. Available in Coffee Oak (seen here), as well as Matte Black and Light Aged Ash.\nMade in the U.S.","title":"Tonal Accessories Shelf (Coffee Oak)","image_url":"https://cdn.shopify.com/s/files/1/0680/3456/0224/files/Coffee-Oak-with-products.webp?v=1721748682","image_alt_text":"","price":"$272.99","variant_id":"gid://shopify/ProductVariant/46081522368736"}]}} --> <div class="wp-block-remote-data-blocks-shopify-vip-store rdb-container"><!-- wp:group {"metadata":{"categories":["Remote Data Blocks"],"patternName":"remote-data-blocks/shopify-vip-store/pattern","name":"Shopify (vip-store) Data"},"layout":{"type":"constrained"}} --> <div class="wp-block-group"><!-- wp:columns --> <div class="wp-block-columns"><!-- wp:column {"width":"33%"} --> <div class="wp-block-column" style="flex-basis:33%"><!-- wp:image {"metadata":{"bindings":{"alt":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/shopify-vip-store","field":"image_alt_text"}},"url":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/shopify-vip-store","field":"image_url"}}},"name":"Image URL"}} --> <figure class="wp-block-image"><img src="https://cdn.shopify.com/s/files/1/0680/3456/0224/files/Coffee-Oak-with-products.webp?v=1721748682" alt=""/></figure> <!-- /wp:image --></div> <!-- /wp:column --> <!-- wp:column --> <div class="wp-block-column"><!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/shopify-vip-store","field":"title"}}},"name":"Title"}} --> <h2 class="wp-block-heading">Tonal Accessories Shelf (Coffee Oak)</h2> <!-- /wp:heading --> <!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/shopify-vip-store","field":"description"}}},"name":"Product description"}} --> <p>Our floating shelf is the perfect way to store all your Tonal accessories. Its sleek, versatile design makes this an easy fit with any style room. Available in Coffee Oak (seen here), as well as Matte Black and Light Aged Ash. Made in the U.S.</p> <!-- /wp:paragraph --> <!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/shopify-vip-store","field":"price"}}},"name":"Item price"}} --> <p>$272.99</p> <!-- /wp:paragraph --></div> <!-- /wp:column --></div> <!-- /wp:columns --></div> <!-- /wp:group --></div> <!-- /wp:remote-data-blocks/shopify-vip-store --> <!-- wp:remote-data-blocks/shopify-vip-store {"remoteData":{"blockName":"remote-data-blocks/shopify-vip-store","isCollection":false,"metadata":{"last_updated":{"name":"Last updated","type":"string","value":"2024-10-07 14:44:01"},"total_count":{"name":"Total count","type":"number","value":1}},"queryInput":{"id":"gid://shopify/Product/8642627207392"},"resultId":"ea7d7dae-4888-42c5-b64c-2b69ccbb958e","results":[{"description":"No detail is too small. Tonal’s proprietary T-Locks let you swap out Tonal accessories with a quick push and twist to lock everything in place.","title":"T-Locks (Pack of 4)","image_url":"https://cdn.shopify.com/s/files/1/0680/3456/0224/files/T-Locks.webp?v=1721746444","image_alt_text":"","price":"$42.99","variant_id":"gid://shopify/ProductVariant/46081259307232"}]}} --> <div class="wp-block-remote-data-blocks-shopify-vip-store rdb-container"><!-- wp:group {"metadata":{"categories":["Remote Data Blocks"],"patternName":"remote-data-blocks/shopify-vip-store/pattern","name":"Shopify (vip-store) Data"},"layout":{"type":"constrained"}} --> <div class="wp-block-group"><!-- wp:columns --> <div class="wp-block-columns"><!-- wp:column {"width":"33%"} --> <div class="wp-block-column" style="flex-basis:33%"><!-- wp:image {"metadata":{"bindings":{"alt":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/shopify-vip-store","field":"image_alt_text"}},"url":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/shopify-vip-store","field":"image_url"}}},"name":"Image URL"}} --> <figure class="wp-block-image"><img src="https://cdn.shopify.com/s/files/1/0680/3456/0224/files/T-Locks.webp?v=1721746444" alt=""/></figure> <!-- /wp:image --></div> <!-- /wp:column --> <!-- wp:column --> <div class="wp-block-column"><!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/shopify-vip-store","field":"title"}}},"name":"Title"}} --> <h2 class="wp-block-heading">T-Locks (Pack of 4)</h2> <!-- /wp:heading --> <!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/shopify-vip-store","field":"description"}}},"name":"Product description"}} --> <p>No detail is too small. Tonal’s proprietary T-Locks let you swap out Tonal accessories with a quick push and twist to lock everything in place.</p> <!-- /wp:paragraph --> <!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/shopify-vip-store","field":"price"}}},"name":"Item price"}} --> <p>$42.99</p> <!-- /wp:paragraph --></div> <!-- /wp:column --></div> <!-- /wp:columns --></div> <!-- /wp:group --></div> <!-- /wp:remote-data-blocks/shopify-vip-store --> <!-- wp:remote-data-blocks/conference-event {"remoteData":{"blockName":"remote-data-blocks/conference-event","isCollection":false,"metadata":{"last_updated":{"name":"Last updated","type":"string","value":"2024-10-07 14:44:10"},"total_count":{"name":"Total count","type":"number","value":1}},"queryInput":{"record_id":"rec1eYD2JFtevz39u"},"resultId":"268c2a0f-2322-4197-b915-e3cede1d1c83","results":[{"id":"rec1eYD2JFtevz39u","title":"Break","location":"Pearl room","type":"Panel"}]}} --> <div class="wp-block-remote-data-blocks-conference-event rdb-container"><!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/conference-event","field":"title"}}},"name":"Title"}} --> <h2 class="wp-block-heading">Break</h2> <!-- /wp:heading --> <!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/conference-event","field":"location"}}},"name":"Location"}} --> <p>Pearl room</p> <!-- /wp:paragraph --> <!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/conference-event","field":"type"}}},"name":"Type"}} --> <p>Panel</p> <!-- /wp:paragraph --></div> <!-- /wp:remote-data-blocks/conference-event -->',
		] );
	}

	public function testRecordEventDoesNothingIfInstanceIsNotSet(): void {
		/** @var TracksAnalytics|MockObject */
		$obj = new TracksAnalytics();

		$result = $obj->record_event( 'name', [] );

		$this->assertEquals( false, $result );
	}

	public function testRecordEventTracksTheEventIfInstanceIsSet(): void {
		$mock_tracks = $this->getMockBuilder( MockTracks::class )->onlyMethods( [ 'record_event' ] )->getMock();
		$mock_tracks->expects( $this->exactly( 1 ) )->method( 'record_event' )->with( 'event_name', [ 'event_props' ] );
		/** @var TracksAnalytics|MockObject */
		$obj = new TracksAnalytics();
		set_private_property( TracksAnalytics::class, $obj, 'instance', $mock_tracks );

		$result = $obj->record_event( 'event_name', [ 'event_props' ] );

		$this->assertEquals( true, $result );
	}
}
