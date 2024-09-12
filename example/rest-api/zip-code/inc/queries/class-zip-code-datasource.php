<?php

namespace RemoteDataBlocks\Example\ZipCode;

use RemoteDataBlocks\Config\HttpDatasource;

class ZipCodeDatasource extends HttpDatasource {
	public function get_display_name(): string {
		return 'Zip Code Datasource';
	}

	public function get_endpoint(): string {
		return 'https://api.zippopotam.us/us/';
	}

	public function get_request_headers(): array {
		return [
			'Content-Type' => 'application/json',
		];
	}
}
