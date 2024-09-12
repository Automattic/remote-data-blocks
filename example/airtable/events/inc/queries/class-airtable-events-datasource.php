<?php

namespace RemoteDataBlocks\Example\Airtable\Events;

use RemoteDataBlocks\Config\HttpDatasource;

class AirtableEventsDatasource extends HttpDatasource {
	public function __construct( private array $config ) {}

	public function get_display_name(): string {
		return 'Events';
	}

	public function get_endpoint(): string {
		return 'https://api.airtable.com/v0/' . $this->config[ 'base' ][ 'id' ] . '/' . $this->config[ 'table' ][ 'id' ];
	}

	public function get_request_headers(): array {
		return [
			'Authorization' => sprintf( 'Bearer %s', $this->config[ 'token' ] ),
			'Content-Type'  => 'application/json',
		];
	}
}
