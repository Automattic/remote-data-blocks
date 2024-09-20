<?php
namespace RemoteDataBlocks\Tests\Mocks;

use RemoteDataBlocks\Config\Datasource\DatasourceInterface;
use RemoteDataBlocks\Config\Datasource\HttpDatasource;

class MockDatasource extends HttpDatasource {
	private $endpoint = 'https://example.com/api';
	private $headers  = [ 'Content-Type' => 'application/json' ];

	const MOCK_CONFIG = [
		'service' => 'mock',
		'api_key' => '1234567890',
	];

	const SERVICE_SCHEMA = [
		'api_key' => [
			'type' => 'string',
		],
	];

	public function get_display_name(): string {
		return 'Mock Datasource';
	}

	public function get_endpoint(): string {
		return $this->endpoint;
	}

	public function get_request_headers(): array {
		return $this->headers;
	}

	public function set_endpoint( string $endpoint ): void {
		$this->endpoint = $endpoint;
	}

	public function set_headers( array $headers ): void {
		$this->headers = $headers;
	}
	
	public static function get_config_schema(): array {
		return array_merge( DatasourceInterface::BASE_SCHEMA, self::SERVICE_SCHEMA );
	}
}
