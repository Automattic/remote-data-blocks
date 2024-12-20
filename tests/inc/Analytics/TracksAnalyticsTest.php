<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Analytics;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use RemoteDataBlocks\Analytics\TracksAnalytics;
use RemoteDataBlocks\Analytics\EnvironmentConfig;
use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\Integrations\Shopify\ShopifyDataSource;

// Define a mock class for Tracks.
if ( ! class_exists( 'Automattic\VIP\Telemetry\Tracks' ) ) {
	class MockTracks {
		/**
		 * @psalm-suppress PossiblyUnusedParam
		 */
		public function record_event( $_name, $_props ) {}
	}
}

class TracksAnalyticsTest extends TestCase {
	public function setUp(): void {
		$GLOBALS['__wordpress_actions'] = [];
	}

	public function tearDown(): void {
		TracksAnalytics::reset();
	}

	public function testInitDoesNotSetTracksIfLibraryIsNotPresent(): void {
		TracksAnalytics::init( new EnvironmentConfig() );

		$this->assertEquals( null, TracksAnalytics::get_instance() );
	}

	public function testInitDoesNotSetTracksOnLocalEnvironment(): void {
		/** @var MockObject|EnvironmentConfig */
		$env_config_mock = $this->getMockBuilder( EnvironmentConfig::class )->onlyMethods( [ 'get_tracks_lib_class', 'is_local_env', 'is_enabled_via_filter' ] )->getMock();
		$env_config_mock->method( 'get_tracks_lib_class' )->with()->willReturn( MockTracks::class );
		$env_config_mock->method( 'is_local_env' )->with()->willReturn( true );
		$env_config_mock->method( 'is_enabled_via_filter' )->with()->willReturn( true );

		TracksAnalytics::init( $env_config_mock );

		$this->assertEquals( null, TracksAnalytics::get_instance() );
	}

	public function testInitDoesSetTracksIfTrackingIsEnabledViaFilter(): void {
		/** @var MockObject|EnvironmentConfig */
		$env_config_mock = $this->getMockBuilder( EnvironmentConfig::class )->onlyMethods( [ 'is_enabled_via_filter', 'get_tracks_lib_class', 'get_remote_data_blocks_properties' ] )->getMock();
		$env_config_mock->method( 'is_enabled_via_filter' )->with()->willReturn( true );
		$env_config_mock->method( 'get_tracks_lib_class' )->with()->willReturn( MockTracks::class );
		$env_config_mock->expects( $this->once() )->method( 'get_remote_data_blocks_properties' )->with();

		TracksAnalytics::init( $env_config_mock );

		$this->assertInstanceOf( MockTracks::class, TracksAnalytics::get_instance() );
		$this->assertEquals( [ 'activated_plugin', 'deactivated_plugin', 'save_post' ], array_keys( $GLOBALS['__wordpress_actions'] ) );
	}

	public function testInitDoesSetTracksIfTrackingIsEnabledOnVipSite(): void {
		/** @var MockObject|EnvironmentConfig */
		$env_config_mock = $this->getMockBuilder( EnvironmentConfig::class )->onlyMethods( [ 'is_wpvip_site', 'get_tracks_lib_class', 'get_remote_data_blocks_properties' ] )->getMock();
		$env_config_mock->method( 'is_wpvip_site' )->with()->willReturn( true );
		$env_config_mock->method( 'get_tracks_lib_class' )->with()->willReturn( MockTracks::class );
		$env_config_mock->expects( $this->once() )->method( 'get_remote_data_blocks_properties' )->with();

		TracksAnalytics::init( $env_config_mock );

		$this->assertInstanceOf( MockTracks::class, TracksAnalytics::get_instance() );
		$this->assertEquals( [ 'activated_plugin', 'deactivated_plugin', 'save_post' ], array_keys( $GLOBALS['__wordpress_actions'] ) );
	}

	public function testGetGlobalProperties(): void {
		/** @var MockObject|EnvironmentConfig */
		$env_config_mock = $this->getMockBuilder( EnvironmentConfig::class )->onlyMethods( [ 'get_tracks_core_properties' ] )->getMock();
		$env_config_mock->expects( $this->exactly( 2 ) )->method( 'get_tracks_core_properties' )->with()->willReturn( [ 'vip_env' => '123' ] );

		TracksAnalytics::init( $env_config_mock );

		$this->assertEquals( [
			'plugin_version' => '',
			'vip_env' => '123',
		], TracksAnalytics::get_global_properties() );
	}

	public function testTrackPluginActivationDoesNotRecordEventIfPluginIsNotRDB(): void {
		/** @var MockObject|EnvironmentConfig */
		$env_config_mock = $this->getMockBuilder( EnvironmentConfig::class )->onlyMethods( [ 'is_remote_data_blocks_plugin' ] )->getMock();
		$env_config_mock->method( 'is_remote_data_blocks_plugin' )->with()->willReturn( false );

		/** @var MockTracks|MockObject */
		$tracks_mock = $this->getMockBuilder( MockTracks::class )->onlyMethods( [ 'record_event' ] )->getMock();
		$tracks_mock->expects( $this->exactly( 0 ) )->method( 'record_event' );

		set_private_property( TracksAnalytics::class, null, 'instance', $tracks_mock );
		TracksAnalytics::init( $env_config_mock );
		TracksAnalytics::track_plugin_activation( 'plugin_path' );
	}

	public function testTrackPluginActivationDoesRecordEventIfPluginIsRDB(): void {
		/** @var MockObject|EnvironmentConfig */
		$env_config_mock = $this->getMockBuilder( EnvironmentConfig::class )->onlyMethods( [ 'is_remote_data_blocks_plugin' ] )->getMock();
		$env_config_mock->method( 'is_remote_data_blocks_plugin' )->with()->willReturn( true );

		/** @var MockTracks|MockObject */
		$tracks_mock = $this->getMockBuilder( MockTracks::class )->onlyMethods( [ 'record_event' ] )->getMock();
		$tracks_mock->expects( $this->exactly( 1 ) )->method( 'record_event' )->with( 'remotedatablocks_plugin_toggle', $this->isType( 'array' ) );

		set_private_property( TracksAnalytics::class, null, 'instance', $tracks_mock );
		TracksAnalytics::init( $env_config_mock );
		TracksAnalytics::track_plugin_activation( 'plugin_path' );
	}

	public function testTrackPluginDeactivationDoesNotRecordEventIfPluginIsNotRDB(): void {
		/** @var MockObject|EnvironmentConfig */
		$env_config_mock = $this->getMockBuilder( EnvironmentConfig::class )->onlyMethods( [ 'is_remote_data_blocks_plugin' ] )->getMock();
		$env_config_mock->method( 'is_remote_data_blocks_plugin' )->with()->willReturn( false );

		/** @var MockTracks|MockObject */
		$tracks_mock = $this->getMockBuilder( MockTracks::class )->onlyMethods( [ 'record_event' ] )->getMock();
		$tracks_mock->expects( $this->exactly( 0 ) )->method( 'record_event' );

		set_private_property( TracksAnalytics::class, null, 'instance', $tracks_mock );
		TracksAnalytics::init( $env_config_mock );
		TracksAnalytics::track_plugin_deactivation( 'plugin_path' );
	}

	public function testTrackPluginDeactivationDoesRecordEventIfPluginIsRDB(): void {
		/** @var MockObject|EnvironmentConfig */
		$env_config_mock = $this->getMockBuilder( EnvironmentConfig::class )->onlyMethods( [ 'is_remote_data_blocks_plugin' ] )->getMock();
		$env_config_mock->method( 'is_remote_data_blocks_plugin' )->with()->willReturn( true );

		/** @var MockTracks|MockObject */
		$tracks_mock = $this->getMockBuilder( MockTracks::class )->onlyMethods( [ 'record_event' ] )->getMock();
		$tracks_mock->expects( $this->exactly( 1 ) )->method( 'record_event' )->with( 'remotedatablocks_plugin_toggle', $this->isType( 'array' ) );

		set_private_property( TracksAnalytics::class, null, 'instance', $tracks_mock );
		TracksAnalytics::init( $env_config_mock );
		TracksAnalytics::track_plugin_deactivation( 'plugin_path' );
	}

	public function testTrackRemoteDataBlocksUsageDoesNotTrackEventIfUsageShouldNotBeTracked(): void {
		/** @var MockObject|EnvironmentConfig */
		$env_config_mock = $this->getMockBuilder( EnvironmentConfig::class )->onlyMethods( [ 'should_track_post_having_remote_data_blocks' ] )->getMock();
		$env_config_mock->method( 'should_track_post_having_remote_data_blocks' )->with( 1 )->willReturn( false );

		/** @var MockTracks|MockObject */
		$tracks_mock = $this->getMockBuilder( MockTracks::class )->onlyMethods( [ 'record_event' ] )->getMock();
		$tracks_mock->expects( $this->exactly( 0 ) )->method( 'record_event' );

		set_private_property( TracksAnalytics::class, null, 'instance', $tracks_mock );
		TracksAnalytics::init( $env_config_mock );
		TracksAnalytics::track_remote_data_blocks_usage( 1, (object) [] );
	}

	public function testTrackRemoteDataBlocksUsageDoesNotTrackEventIfPostStatusIsNotPublish(): void {
			/** @var MockObject|EnvironmentConfig */
			$env_config_mock = $this->getMockBuilder( EnvironmentConfig::class )->onlyMethods( [ 'should_track_post_having_remote_data_blocks' ] )->getMock();
			$env_config_mock->method( 'should_track_post_having_remote_data_blocks' )->with( 1 )->willReturn( true );

			/** @var MockTracks|MockObject */
			$tracks_mock = $this->getMockBuilder( MockTracks::class )->onlyMethods( [ 'record_event' ] )->getMock();
			$tracks_mock->expects( $this->exactly( 0 ) )->method( 'record_event' );

			set_private_property( TracksAnalytics::class, null, 'instance', $tracks_mock );
			TracksAnalytics::init( $env_config_mock );
			TracksAnalytics::track_remote_data_blocks_usage( 1, (object) [ 'post_status' => 'draft' ] );
	}

	public function testTrackRemoteDataBlocksUsageDoesNotTrackEventIfPostContentHaveNoRemoteBlocks(): void {
		/** @var MockObject|EnvironmentConfig */
		$env_config_mock = $this->getMockBuilder( EnvironmentConfig::class )->onlyMethods( [ 'should_track_post_having_remote_data_blocks' ] )->getMock();
		$env_config_mock->method( 'should_track_post_having_remote_data_blocks' )->with( 1 )->willReturn( true );

		// Setup data sources.
		ConfigStore::init();
		ConfigStore::set_block_configuration( 'remote-data-blocks/shopify-vip-store', [
			'queries' => [
				'display' => HttpQuery::from_array( [
					'data_source' => ShopifyDataSource::from_array( [
						'access_token' => 'token',
						'store_name' => 'B. Walton',
					] ),
				] ),
			],
		] );

		/** @var MockTracks|MockObject */
		$tracks_mock = $this->getMockBuilder( MockTracks::class )->onlyMethods( [ 'record_event' ] )->getMock();
		$tracks_mock->expects( $this->exactly( 0 ) )->method( 'record_event' );

		set_private_property( TracksAnalytics::class, null, 'instance', $tracks_mock );
		TracksAnalytics::init( $env_config_mock );
		TracksAnalytics::track_remote_data_blocks_usage( 1, (object) [
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_content' => '<p>No remote data blocks</p>',
		] );
	}

	public function testTrackRemoteDataBlocksUsageDoesTrackEventIfPostContentHaveRemoteBlocks(): void {
		/** @var MockObject|EnvironmentConfig */
		$env_config_mock = $this->getMockBuilder( EnvironmentConfig::class )->onlyMethods( [ 'should_track_post_having_remote_data_blocks' ] )->getMock();
		$env_config_mock->method( 'should_track_post_having_remote_data_blocks' )->with( 1 )->willReturn( true );

		// Setup data sources.
		ConfigStore::init();
		ConfigStore::set_block_configuration( 'remote-data-blocks/shopify-vip-store', [
			'queries' => [
				'display' => HttpQuery::from_array( [
					'data_source' => ShopifyDataSource::from_array( [
						'service_config' => [
							'__version' => 1,
							'access_token' => 'token',
							'display_name' => 'Shopify Source',
							'store_name' => 'B. Walton',
						],
					] ),
					'output_schema' => [ 'type' => 'string' ],
				] ),
			],
		] );

		ConfigStore::set_block_configuration( 'remote-data-blocks/conference-event', [
			'queries' => [
				'display' => HttpQuery::from_array( [
					'data_source' => HttpDataSource::from_array( [
						'service_config' => [
							'__version' => 1,
							'display_name' => 'HTTP Source',
							'endpoint' => 'https://example.com/api/v1',
						],
					] ),
					'output_schema' => [ 'type' => 'string' ],
				] ),
			],
		] );

		/** @var MockTracks|MockObject */
		$tracks_mock = $this->getMockBuilder( MockTracks::class )->onlyMethods( [ 'record_event' ] )->getMock();
		$tracks_mock->expects( $this->exactly( 1 ) )->method( 'record_event' )->with( 'remotedatablocks_blocks_usage_stats', [
			'post_status' => 'publish',
			'post_type' => 'post',
			'shopify_data_source_count' => 2,
			'remote_data_blocks_total_count' => 3,
			'generic-http_data_source_count' => 1,
		] );

		set_private_property( TracksAnalytics::class, null, 'instance', $tracks_mock );
		TracksAnalytics::init( $env_config_mock );
		TracksAnalytics::track_remote_data_blocks_usage( 1, (object) [
			'post_type' => 'post',
			'post_status' => 'publish',
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

	public function testResetMethod(): void {
		$obj = new TracksAnalytics();
		set_private_property( TracksAnalytics::class, $obj, 'instance', new MockTracks() );
		TracksAnalytics::init( new EnvironmentConfig() );

		$this->assertInstanceOf( MockTracks::class, TracksAnalytics::get_instance() );
		$this->assertInstanceOf( EnvironmentConfig::class, TracksAnalytics::get_env_config() );

		TracksAnalytics::reset();

		$this->assertEquals( null, TracksAnalytics::get_instance() );
		$this->assertEquals( null, TracksAnalytics::get_env_config() );
	}
}
