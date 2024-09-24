<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Sanitization;

/**
 * Interface for providing data sanitization.
 *
 * @see Sanitizer for an implementation
 */
interface SanitizerInterface {
	/**
	 * Constructor.
	 * 
	 * @param array $schema
	 */
	public function __construct( array $schema );

	/**
	 * Sanitize data according to a schema.
	 *
	 * @param array $data
	 *
	 * @return array The sanitized data.
	 */
	public function sanitize( array $data ): array;
}
