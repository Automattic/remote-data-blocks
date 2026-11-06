<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Telemetry;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Telemetry\Telemetry;

class TelemetryClientPropertiesTest extends TestCase {
	public function test_client_properties_are_null_without_the_vip_telemetry_library(): void {
		$this->assertFalse( class_exists( '\\Automattic\\VIP\\Telemetry\\Telemetry' ), 'This test assumes the mu-plugin library is not loaded.' );

		$this->assertNull( Telemetry::get_client_properties() );
	}

	/**
	 * In its own process, because the library stand-ins it loads cannot be undone.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_client_properties_are_the_library_core_properties_and_the_plugin_version(): void {
		require_once __DIR__ . '/fixtures/vip-telemetry-class.php';
		require_once __DIR__ . '/fixtures/vip-tracks-utils.php';

		$this->assertSame(
			[
				'vip_env' => 'production',
				'vip_org' => 42,
				'_ui' => 'abc',
				'_ut' => 'vip:user_email',
				'plugin_version' => Telemetry::get_global_properties()['plugin_version'],
			],
			Telemetry::get_client_properties()
		);
	}
}
