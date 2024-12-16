<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use WP_Error;

class MockDataSource extends HttpDataSource {
	private $endpoint = 'https://example.com/api';
	private $headers = [ 'Content-Type' => 'application/json' ];

	public const MOCK_CONFIG = [
		'uuid' => 'e3458c42-4cf4-4214-aaf6-3628e33ed07a',
		'service' => 'mock',
		'slug' => 'mock-thingy-1',
		'api_key' => '1234567890',
	];

	protected const SERVICE_SCHEMA = [
		'type' => 'object',
		'properties' => [
			'api_key' => [
				'type' => 'string',
			],
		],
	];

	public function get_display_name(): string {
		return 'Mock Data Source';
	}

	public function get_endpoint(): string {
		return $this->endpoint;
	}

	public function get_request_headers(): array|WP_Error {
		return $this->headers;
	}

	/**
	 * Override the endpoint.
	 */
	public function set_endpoint( string $endpoint ): void {
		$this->endpoint = $endpoint;
	}

	/**
	 * Override the headers.
	 */
	public function set_headers( array $headers ): void {
		$this->headers = $headers;
	}
}
