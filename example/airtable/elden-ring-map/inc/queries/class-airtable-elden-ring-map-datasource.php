<?php

namespace RemoteDataBlocks\Example\Airtable\EldenRingMap;

use RemoteDataBlocks\Config\HttpDatasource;

class AirtableEldenRingMapDatasource extends HttpDatasource {
	const MAPS_TABLE      = 'tblS3OYo8tZOg04CP';
	const LOCATIONS_TABLE = 'tblc82R9msH4Yh6ZX';

	public function __construct( private string $access_token ) {}

	public function get_display_name(): string {
		return 'Elden Ring Map';
	}

	public function get_endpoint(): string {
		return 'https://api.airtable.com/v0/appqI3sJ9R2NcML8Y';
	}

	public function get_request_headers(): array {
		return [
			'Authorization' => sprintf( 'Bearer %s', $this->access_token ),
			'Content-Type'  => 'application/json',
		];
	}
}
