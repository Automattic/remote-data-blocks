<?php

namespace RemoteDataBlocks\Example\IGDB;

use RemoteDataBlocks\Config\HttpDatasource;

class IGDBDatasource extends HttpDatasource {
	public function get_endpoint(): string {
		return 'https://api.igdb.com/v4';
	}

	public function get_request_headers(): array {
		return [
			'Content-Type' => 'application/json',
			'Client-ID'    => '9i9c7y36ka7nj4jums838zoabpi7a5',
			'Authorization' => 'Bearer pug08l91yn7o57duilwn4fpykxfa8v'
		];
	}
}
