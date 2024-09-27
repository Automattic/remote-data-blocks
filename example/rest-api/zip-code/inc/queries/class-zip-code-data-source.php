<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ZipCode;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;

class ZipCodeDataSource extends HttpDataSource {
	public function get_display_name(): string {
		return 'Zip Code Data Source';
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
