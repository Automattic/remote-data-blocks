<?php

namespace RemoteDataBlocks\Example\ArtInstituteOfChicago;

use RemoteDataBlocks\Config\Datasource\HttpDatasource;

class ArtInstituteOfChicagoDatasource extends HttpDatasource {
	public function get_display_name(): string {
		return 'Art Institute of Chicago';
	}

	public function get_endpoint(): string {
		return 'https://api.artic.edu/api/v1/artworks';
	}

	public function get_request_headers(): array {
		return [
			'Content-Type' => 'application/json',
		];
	}

	public static function get_config_schema(): array {
		return [];
	}
}
