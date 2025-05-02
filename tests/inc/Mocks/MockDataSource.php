<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Tests\Mocks\MockValidator;
use RemoteDataBlocks\Validation\ValidatorInterface;
use WP_Error;

class MockDataSource extends HttpDataSource {
	public const MOCK_CONFIG = [
		'display_name' => 'Mock Data Source',
		'endpoint' => 'https://example.com/api',
		'request_headers' => [
			'Content-Type' => 'application/json',
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
		if ( ! isset( $config['request_headers'] ) ) {
			return $config;
		}

		// Add an x-user-id request header to the config if it's not already set.
		// If it is set, ensure it's an integer.
		// Throw an error if it's not an integer.
		// Together, this correctly simulates the behavior of the HttpDataSource::migrate_config method.
		if ( isset( $config['request_headers']['x-user-id'] ) && ! isset( $config['request_headers']['x-api-user'] ) ) {
			$config['request_headers']['x-api-user'] = $config['request_headers']['x-user-id'];
			unset( $config['request_headers']['x-user-id'] );
			return $config;
		}

		if ( isset( $config['request_headers']['x-api-user'] ) && ! is_int( $config['request_headers']['x-api-user'] ) ) {
			return new WP_Error( 'invalid_x_api_user', 'x-api-user header must be an integer' );
		}

		return $config;
	}
}
