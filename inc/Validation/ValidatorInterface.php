<?php

namespace RemoteDataBlocks\Validation;

use WP_Error;

/**
 * Interface for providing data validation.
 *
 * @see Validator for an implementation
 */
interface ValidatorInterface {
	/**
	 * Constructor.
	 * 
	 * @param array $schema
	 */
	public function __construct( array $schema );

	/**
	 * Validate data against a schema.
	 *
	 * @param string|array|object|null $data
	 *
	 * @return true|\WP_Error WP_Error for invalid data, true otherwise
	 */
	public function validate( string|array|object|null $data ): bool|WP_Error;
}
