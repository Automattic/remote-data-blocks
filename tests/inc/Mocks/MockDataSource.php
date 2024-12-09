<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Tests\Mocks\MockValidator;

class MockDataSource extends HttpDataSource {
	private string $endpoint = 'https://example.com/api';
	private array $headers = [ 'Content-Type' => 'application/json' ];

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

	public static function create(): static {
		return self::from_array( self::MOCK_CONFIG, new MockValidator() );
	}

	public function get_display_name(): string {
		return 'Mock Data Source';
	}

	public function get_endpoint(): string {
		return $this->endpoint;
	}

	/**
	 * @inheritDoc
	 */
	public function get_request_headers(): array {
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
