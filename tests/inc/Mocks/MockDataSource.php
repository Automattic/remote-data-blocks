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
			'__version' => 1,
			'display_name' => 'Mock Data Source',
			'endpoint' => 'https://example.com/api',
			'request_headers' => [
				'Content-Type' => 'application/json',
			],
		],
	];

	public static function create( ?array $config = self::MOCK_CONFIG, ?ValidatorInterface $validator = null ): static|WP_Error {
		return self::from_array( $config, $validator ?? new MockValidator() );
	}

	/**
	 * Override the endpoint.
	 */
	public function set_endpoint( string $endpoint ): void {
		$this->config['endpoint'] = $endpoint;
	}

	/**
	 * Override the migrate_config method to adjust the config for testing.
	 */
	public static function migrate_config( array $config ): array|WP_Error {
		// Add a testUserId to the config if it's not already set.
		// If it is set, ensure it's an integer.
		// Throw an error if it's not an integer.
		// Together, this correctly simulates the behavior of the HttpDataSource::migrate_config method.
		if ( ! isset( $config['service_config']['testUserId'] ) ) {
			$config['service_config']['testUserId'] = 1;
		} elseif ( ! is_int( $config['service_config']['testUserId'] ) ) {
			return new WP_Error( 'invalid_test_user_id', 'testUserId must be an integer' );
		}

		return $config;
	}
}
