<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config;

interface ArraySerializableInterface {
	/**
	 * Creates an instance of the class from an array representation.
	 *
	 * This static method is used to construct an object of the implementing class
	 * using data provided in an array format. It's particularly useful for
	 * deserialization or when creating objects from structured data (e.g., JSON).
	 *
	 * @param array $config An associative array containing the configuration or
	 *                      data needed to create an instance of the class.
	 * @return static       Returns a new instance of the implementing class.
	 */
	public static function from_array( array $data );

	/**
	 * Converts the current object to an array representation.
	 *
	 * This method serializes the object's state into an array format. It's useful
	 * for data persistence, API responses, or any scenario where the object needs
	 * to be represented as a simple array structure.
	 *
	 * @return array An associative array representing the object's current state.
	 */
	public function to_array(): array;
}
