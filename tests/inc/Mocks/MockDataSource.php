<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Tests\Mocks\MockValidator;
use RemoteDataBlocks\Validation\ValidatorInterface;
use WP_Error;

class MockDataSource extends HttpDataSource {
	public const MOCK_CONFIG = [
		'service' => 'mock',
		'service_config' => [
			'display_name' => 'Mock Data Source',
			'endpoint' => 'https://example.com/api',
			'request_headers' => [
				'Content-Type' => 'application/json',
			],
		],
	];

	public static function from_array( ?array $config = self::MOCK_CONFIG, ?ValidatorInterface $validator = null ): self|WP_Error {
		return parent::from_array( $config, $validator ?? new MockValidator() );
	}

	/**
	 * Override the endpoint.
	 */
	public function set_endpoint( string $endpoint ): void {
		$this->config['endpoint'] = $endpoint;
	}
}
