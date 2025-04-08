<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

class MockTelemetry {
	public function record_event( string $_name, array $_props ): void {}
}
