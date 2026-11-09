<?php declare( strict_types = 1 );

namespace RemoteDataBlocks\Tests\PluginSettings;

use Mockery;
use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Editor\Assets\Assets;
use RemoteDataBlocks\PluginSettings\PluginSettings;
use RemoteDataBlocks\Telemetry\Telemetry;

/**
 * In its own process, because the stand-ins it loads cannot be undone.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SettingsAssetsTest extends TestCase {
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_settings_page_receives_the_tracks_client_properties(): void {
		require_once __DIR__ . '/fixtures/settings-assets-functions.php';
		require_once __DIR__ . '/../Telemetry/fixtures/vip-telemetry-class.php';
		require_once __DIR__ . '/../Telemetry/fixtures/vip-tracks-utils.php';

		Mockery::mock( 'alias:' . Assets::class )->shouldReceive( 'enqueue_build_asset' );

		PluginSettings::enqueue_settings_assets( 'settings_page_remote-data-blocks-settings' );

		$localized = $GLOBALS['rdb_test_localized_scripts']['remote-data-blocks-settings'] ?? [];
		$this->assertSame( 'REMOTE_DATA_BLOCKS_SETTINGS', $localized['object_name'] ?? null );
		$this->assertNotNull( Telemetry::get_client_properties() );
		$this->assertSame( Telemetry::get_client_properties(), $localized['data']['tracks_global_properties'] ?? null );
	}
}
