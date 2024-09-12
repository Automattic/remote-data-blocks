<?php

namespace RemoteDataBlocks\Example\Airtable\Events;

use RemoteDataBlocks\Config\HttpDatasource;

class AirtableEventsDatasource extends HttpDatasource {
	public function __construct( private string $access_token ) {}

	public function get_display_name(): string {
		return 'Events';
	}

	public function get_endpoint(): string {
		return 'https://api.airtable.com/v0/appVQ2PAl95wQSo9S/tblyGtuxblLtmoqMI';
	}

	public function get_request_headers(): array {
		return [
			'Authorization' => sprintf( 'Bearer %s', $this->access_token ),
			'Content-Type'  => 'application/json',
		];
	}
}
