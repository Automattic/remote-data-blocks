<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Telemetry;

use PHPUnit\Framework\MockObject\MockObject;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use WP_UnitTestCase;
use RemoteDataBlocks\Telemetry\Telemetry;
use RemoteDataBlocks\Tests\Mocks\MockQuery;
use RemoteDataBlocks\Tests\Mocks\MockTelemetry;
use function do_action;

class TelemetryTest extends WP_UnitTestCase {
	private string $plugin_path;
	private MockObject $mock_telemetry;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_path = '/path/to/plugin';
		$this->mock_telemetry = $this->createMock( MockTelemetry::class );
		Telemetry::reset();
	}

	public function test_track_plugin_activation_calls_record_event(): void {
		$this->mock_telemetry
			->expects( $this->once() )
			->method( 'record_event' )
			->with(
				'plugin_toggle',
				$this->equalTo( [ 'action' => 'activate' ] )
			);

		Telemetry::init( $this->plugin_path, $this->mock_telemetry );
		do_action( 'activated_plugin', $this->plugin_path );
	}

	public function test_track_plugin_activation_does_not_call_record_event_for_other_plugins(): void {
		$this->mock_telemetry
			->expects( $this->never() )
			->method( 'record_event' );

		Telemetry::init( $this->plugin_path, $this->mock_telemetry );
		do_action( 'activated_plugin', '/path/to/other-plugin' );
	}

	public function test_track_plugin_deactivation_calls_record_event(): void {
		$this->mock_telemetry
			->expects( $this->once() )
			->method( 'record_event' )
			->with(
				'plugin_toggle',
				$this->equalTo( [ 'action' => 'deactivate' ] )
			);

		Telemetry::init( $this->plugin_path, $this->mock_telemetry );
		do_action( 'deactivated_plugin', $this->plugin_path );
	}

	public function test_track_plugin_deactivation_does_not_call_record_event_for_other_plugins(): void {
		$this->mock_telemetry
			->expects( $this->never() )
			->method( 'record_event' );

		Telemetry::init( $this->plugin_path, $this->mock_telemetry );
		do_action( 'deactivated_plugin', '/path/to/other-plugin' );
	}

	public function test_track_remote_data_blocks_usage_calls_record_event(): void {
		$post_id = $this->factory()->post->create( [
			'post_status' => 'publish',
			'post_type' => 'post',
			'post_content' => '<!-- wp:remote-data-blocks/example -->',
		] );

		ConfigStore::set_block_configuration( 'remote-data-blocks/example', [
			'queries' => [
				'display' => MockQuery::create(),
			],
		] );

		$this->mock_telemetry
			->expects( $this->once() )
			->method( 'record_event' )
			->with(
				'blocks_usage_stats',
				$this->equalTo( [
					'post_status' => 'publish',
					'post_type' => 'post',
					'remote_data_blocks_total_count' => 1,
					'code-configured_data_source_count' => 1,
				] ),
			);

		Telemetry::init( $this->plugin_path, $this->mock_telemetry );
		do_action( 'save_post', $post_id, get_post( $post_id ) );
	}

	public function test_track_remote_data_blocks_usage_tracks_nested_blocks(): void {
		$post_id = $this->factory()->post->create( [
			'post_status' => 'publish',
			'post_type' => 'post',
			'post_content' => '<!-- wp:group -->
<div class="wp-block-group">
	<!-- wp:remote-data-blocks/example -->
	<div class="wp-block-remote-data-blocks-example"></div>
	<!-- /wp:remote-data-blocks/example -->
</div>
<!-- /wp:group -->',
		] );

		ConfigStore::set_block_configuration( 'remote-data-blocks/example', [
			'queries' => [
				'display' => MockQuery::create(),
			],
		] );

		$this->mock_telemetry
			->expects( $this->once() )
			->method( 'record_event' )
			->with(
				'blocks_usage_stats',
				$this->equalTo( [
					'post_status' => 'publish',
					'post_type' => 'post',
					'remote_data_blocks_total_count' => 1,
					'code-configured_data_source_count' => 1,
				] ),
			);

		Telemetry::init( $this->plugin_path, $this->mock_telemetry );
		do_action( 'save_post', $post_id, get_post( $post_id ) );
	}

	public function test_track_remote_data_blocks_usage_tracks_multiple_nested_blocks(): void {
		$post_id = $this->factory()->post->create( [
			'post_status' => 'publish',
			'post_type' => 'post',
			'post_content' => '<!-- wp:group -->
<div class="wp-block-group">
	<!-- wp:remote-data-blocks/example -->
	<div class="wp-block-remote-data-blocks-example"></div>
	<!-- /wp:remote-data-blocks/example -->
	<!-- wp:columns -->
	<div class="wp-block-columns">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:remote-data-blocks/example -->
			<div class="wp-block-remote-data-blocks-example"></div>
			<!-- /wp:remote-data-blocks/example -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->',
		] );

		ConfigStore::set_block_configuration( 'remote-data-blocks/example', [
			'queries' => [
				'display' => MockQuery::create(),
			],
		] );

		$this->mock_telemetry
			->expects( $this->once() )
			->method( 'record_event' )
			->with(
				'blocks_usage_stats',
				$this->equalTo( [
					'post_status' => 'publish',
					'post_type' => 'post',
					'remote_data_blocks_total_count' => 2,
					'code-configured_data_source_count' => 2,
				] ),
			);

		Telemetry::init( $this->plugin_path, $this->mock_telemetry );
		do_action( 'save_post', $post_id, get_post( $post_id ) );
	}

	public function test_track_remote_data_blocks_usage_tracks_fallback_blocks_in_nested_structure(): void {
		$post_id = $this->factory()->post->create( [
			'post_status' => 'publish',
			'post_type' => 'post',
			'post_content' => '<!-- wp:group -->
<div class="wp-block-group">
	<!-- wp:remote-data-blocks/example -->
	<div class="wp-block-remote-data-blocks-example">
		<!-- wp:remote-data-blocks/no-results -->
		<div class="wp-block-remote-data-blocks-no-results"></div>
		<!-- /wp:remote-data-blocks/no-results -->
	</div>
	<!-- /wp:remote-data-blocks/example -->
	<!-- wp:remote-data-blocks/example -->
	<div class="wp-block-remote-data-blocks-example">
		<!-- wp:remote-data-blocks/no-results {"mode":"error"} -->
		<div class="wp-block-remote-data-blocks-no-results"></div>
		<!-- /wp:remote-data-blocks/no-results -->
	</div>
	<!-- /wp:remote-data-blocks/example -->
</div>
<!-- /wp:group -->',
		] );

		ConfigStore::set_block_configuration( 'remote-data-blocks/example', [
			'queries' => [
				'display' => MockQuery::create(),
			],
		] );

		$this->mock_telemetry
			->expects( $this->once() )
			->method( 'record_event' )
			->with(
				'blocks_usage_stats',
				$this->equalTo( [
					'post_status' => 'publish',
					'post_type' => 'post',
					'remote_data_blocks_total_count' => 2,
					'code-configured_data_source_count' => 2,
					'no_results_fallback_block_count' => 1,
					'error_fallback_block_count' => 1,
				] ),
			);

		Telemetry::init( $this->plugin_path, $this->mock_telemetry );
		do_action( 'save_post', $post_id, get_post( $post_id ) );
	}

	public function test_track_remote_data_blocks_usage_does_not_call_record_event_for_non_published_posts(): void {
		$post_id = $this->factory()->post->create( [
			'post_status' => 'draft',
			'post_type' => 'post',
			'post_content' => '<!-- wp:remote-data-blocks/example -->',
		] );

		$this->mock_telemetry
			->expects( $this->never() )
			->method( 'record_event' );

		Telemetry::init( $this->plugin_path, $this->mock_telemetry );
		do_action( 'save_post', $post_id, get_post( $post_id ) );
	}

	public function test_track_remote_data_blocks_usage_does_not_call_record_event_for_posts_without_remote_blocks(): void {
		$post_id = $this->factory()->post->create( [
			'post_status' => 'publish',
			'post_type' => 'post',
			'post_content' => '<!-- wp:paragraph -->
<p>Regular content without remote data blocks</p>
<!-- /wp:paragraph -->',
		] );

		$this->mock_telemetry
			->expects( $this->never() )
			->method( 'record_event' );

		Telemetry::init( $this->plugin_path, $this->mock_telemetry );
		do_action( 'save_post', $post_id, get_post( $post_id ) );
	}

	public function test_track_remote_data_blocks_usage_tracks_nested_remote_data_blocks(): void {
		$post_id = $this->factory()->post->create( [
			'post_status' => 'publish',
			'post_type' => 'post',
			'post_content' => '<!-- wp:remote-data-blocks/example -->
<div class="wp-block-remote-data-blocks-example">
	<!-- wp:remote-data-blocks/example -->
	<div class="wp-block-remote-data-blocks-example"></div>
	<!-- /wp:remote-data-blocks/example -->
</div>
<!-- /wp:remote-data-blocks/example -->',
		] );

		ConfigStore::set_block_configuration( 'remote-data-blocks/example', [
			'queries' => [
				'display' => MockQuery::create(),
			],
		] );

		$this->mock_telemetry
			->expects( $this->once() )
			->method( 'record_event' )
			->with(
				'blocks_usage_stats',
				$this->equalTo( [
					'post_status' => 'publish',
					'post_type' => 'post',
					'remote_data_blocks_total_count' => 2,
					'code-configured_data_source_count' => 2,
				] ),
			);

		Telemetry::init( $this->plugin_path, $this->mock_telemetry );
		do_action( 'save_post', $post_id, get_post( $post_id ) );
	}
}
