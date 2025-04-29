<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

use RemoteDataBlocks\Validation\ValidatorInterface;
use WP_Error;

class MockValidator implements ValidatorInterface {
	/**
	 * Constructor.
	 *
	 * @param bool $should_pass Whether the validation should pass or fail.
	 */
	public function __construct( private bool $should_pass = true ) {}

	/**
	 * Validate data against a schema.
	 *
	 * @return true|\WP_Error WP_Error for invalid data, true otherwise
	 */
	public function validate( string|array|object|null $data ): bool|WP_Error {
		if ( $this->should_pass ) {
			return true;
		}

		return new WP_Error(
			'mock_validation_error',
			'Mock validation failed',
			[ 'status' => 400 ]
		);
	}
}
