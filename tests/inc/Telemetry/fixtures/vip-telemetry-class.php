<?php declare(strict_types = 1);

namespace Automattic\VIP\Telemetry;

// Stand-in for the VIP Telemetry library class, which only exists in VIP
// mu-plugins. Loaded only by tests that run in their own process.

class Telemetry {}
