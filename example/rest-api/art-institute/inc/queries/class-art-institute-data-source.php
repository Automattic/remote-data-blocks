<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ArtInstituteOfChicago;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use WP_Error;

class ArtInstituteOfChicagoDataSource extends HttpDataSource {
	public function get_display_name(): string {
		return 'Art Institute of Chicago';
	}

	public function get_endpoint(): string {
		return 'https://api.artic.edu/api/v1/artworks';
	}

	public function get_request_headers(): array|WP_Error {
		return [
			'Content-Type' => 'application/json',
		];
	}
}
