<?php declare(strict_types = 1);

namespace Automattic\VIP\Telemetry\Tracks;

// Stand-in for the VIP Telemetry library's Tracks helpers, which only exist in
// VIP mu-plugins. Loaded only by tests that run in their own process.

function get_tracks_core_properties(): array {
	return [
		'vip_env' => 'production',
		'vip_org' => 42,
		'_ui' => 'abc',
		'_ut' => 'vip:user_email',
	];
}
