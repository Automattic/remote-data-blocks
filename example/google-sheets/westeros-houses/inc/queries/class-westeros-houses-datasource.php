<?php

namespace RemoteDataBlocks\Example\GoogleSheets\WesterosHouses;

use RemoteDataBlocks\Config\HttpDatasource;
use RemoteDataBlocks\Config\Auth\GoogleAuth;

class WesterosHousesDatasource extends HttpDatasource {
	private array $credentials;

	public function __construct( string $credentials ) {
		/**
		 * Decodes Base64 encoded JSON string into an array
		 * and assigns it to the $credentials property.
		 */
		$this->credentials = json_decode( base64_decode( $credentials ), true );
	}

	public function get_endpoint(): string {
		return 'https://sheets.googleapis.com/v4/spreadsheets/' .
			'1EHdQg53Doz0B-ImrGz_hTleYeSvkVIk_NSJCOM1FQk0/values/Houses';
	}

	public function get_request_headers(): array {
		$access_token = GoogleAuth::generate_token_from_service_account_key(
			$this->credentials,
			GoogleAuth::GOOGLE_SHEETS_SCOPES
		);

		return [
			'Authorization' => sprintf( 'Bearer %s', $access_token ),
			'Content-Type'  => 'application/json',
		];
	}
}