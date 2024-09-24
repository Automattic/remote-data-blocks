<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

use RemoteDataBlocks\Validation\ValidatorInterface;
use WP_Error;

class MockValidator implements ValidatorInterface {
	private array $schema;
	private bool $should_pass;

	/**
	 * Constructor.
	 * 
	 * @param array $schema
	 * @param bool  $should_pass Whether the validation should pass or fail.
	 */
	public function __construct( array $schema = [], bool $should_pass = true ) {
		$this->schema      = $schema;
		$this->should_pass = $should_pass;
	}

	/**
	 * Validate data against a schema.
	 *
	 * @param string|array|object|null $data
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

	/**
	 * Set whether the validation should pass or fail.
	 *
	 * @param bool $should_pass
	 */
	public function set_should_pass( bool $should_pass ): void {
		$this->should_pass = $should_pass;
	}
}
