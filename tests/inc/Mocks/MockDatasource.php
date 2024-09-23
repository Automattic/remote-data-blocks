<?php
namespace RemoteDataBlocks\Tests\Mocks;

use RemoteDataBlocks\Config\Datasource\DatasourceInterface;
use RemoteDataBlocks\Config\Datasource\HttpDatasource;

class MockDatasource extends HttpDatasource {
	private $endpoint = 'https://example.com/api';
	private $headers  = [ 'Content-Type' => 'application/json' ];

	public const MOCK_CONFIG = [
		'uuid'    => 'e3458c42-4cf4-4214-aaf6-3628e33ed07a',
		'service' => 'mock',
		'slug'    => 'mock-thingy-1',
		'api_key' => '1234567890',
	];

	protected const SERVICE_SCHEMA = [
		'type'       => 'object',
		'properties' => [
			'api_key' => [
				'type' => 'string',
			],
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
}
