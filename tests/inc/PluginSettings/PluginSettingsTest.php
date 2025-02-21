<?php declare( strict_types = 1 );

namespace RemoteDataBlocks\Tests\PluginSettings;

use PHPUnit\Framework\TestCase;
use Mockery;
use RemoteDataBlocks\PluginSettings\PluginSettings;
use RemoteDataBlocks\Store\DataSource\DataSourceConfigManager;
use RemoteDataBlocks\Telemetry\DataSourceTelemetry;
use RemoteDataBlocks\Tests\Mocks\MockWordPressFunctions;

class PluginSettingsTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		MockWordPressFunctions::reset();
	}

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function testSettingsPageContentTracksView(): void {
		// Mock the DataSourceConfigManager::get_all() method
		$mock_configs = [
			[
				'uuid' => '1',
				'service' => 'generic-http',
				'config_source' => 'code',
				'service_config' => [
					'display_name' => 'Test HTTP Source',
					'endpoint' => 'https://api.example.com',
				],
			],
			[
				'uuid' => '2',
				'service' => 'airtable',
				'config_source' => 'storage',
				'service_config' => [
					'display_name' => 'Test Airtable Source',
				],
			],
		];

		// Mock WordPress functions
		MockWordPressFunctions::add_mock_filter( 'esc_html__', 'Loading…' );

		// Mock static methods using Mockery
		$config_manager_mock = Mockery::mock( 'alias:' . DataSourceConfigManager::class );
		$config_manager_mock->shouldReceive( 'get_all' )
			->once()
			->andReturn( $mock_configs );

		$telemetry_mock = Mockery::mock( 'alias:' . DataSourceTelemetry::class );
		$telemetry_mock->shouldReceive( 'track_view' )
			->once()
			->with( Mockery::on( function ( $configs ) use ( $mock_configs ) {
				$this->assertEquals( $mock_configs, $configs );
				return true;
			} ) );

		// Start output buffering to capture printf output
		ob_start();
		
		// Call the method we're testing
		PluginSettings::settings_page_content();
		
		// Get and clean the output buffer
		$output = ob_get_clean();

		// Verify the output
		$this->assertEquals(
			'<div id="remote-data-blocks-settings-wrapper">
				<div id="remote-data-blocks-settings">Loading…</div>
			</div>',
			$output
		);
	}
}
