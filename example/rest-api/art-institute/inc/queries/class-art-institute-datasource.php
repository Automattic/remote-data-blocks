<?php

namespace RemoteDataBlocks\Example\ArtInstituteOfChicago;

use RemoteDataBlocks\Config\HttpDatasource;

class ArtInstituteOfChicagoDatasource extends HttpDatasource {
	public function get_endpoint(): string {
		return 'https://api.artic.edu/api/v1/artworks';
	}

	public function get_request_headers(): array {
		return [
			'Content-Type' => 'application/json',
		];
	}
}