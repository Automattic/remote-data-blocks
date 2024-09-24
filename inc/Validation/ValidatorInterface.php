<?php declare(strict_types = 1);

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
	 */
	public function __construct( array $schema );

	/**
	 * Validate data against a schema.
	 *
	 *
	 * @return true|\WP_Error WP_Error for invalid data, true otherwise
	 */
	public function validate( array $data ): bool|WP_Error;
}
