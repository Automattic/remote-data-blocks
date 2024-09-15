<?php
namespace RemoteDataBlocks\Tests;

use RemoteDataBlocks\Config\Datasource\HttpDatasource;

class TestDatasource extends HttpDatasource {
	public function get_display_name(): string {
		return 'Test Datasource';
	}

	public function get_endpoint(): string {
		return 'https://example.com/api';
	}

	public function get_request_headers(): array {
		return [ 'Content-Type' => 'application/json' ];
	}
}
