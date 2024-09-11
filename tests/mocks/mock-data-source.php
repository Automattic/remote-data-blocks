<?php
namespace RemoteDataBlocks\Test;

use RemoteDataBlocks\Config\HttpDatasource;

class TestDatasource extends HttpDatasource {
	public function get_endpoint(): string {
		return 'https://example.com';
	}

	public function get_request_headers(): array {
		return [ 'Content-Type' => 'application/json' ];
	}
}
