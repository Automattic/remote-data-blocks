<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Utils;

class ArrayUtils {
	/**
	 * Merges duplicate values from an array of arrays based on the specified key.
	 * In case of duplicate values for same key in the inner array,
	 * the value from the first occurence has priority.
	 *
	 * @param array  $array The array of arrays to merge duplicates from.
	 * @param string $key   The key to merge duplicates based on.
	 * @return array The array with duplicates merged.
	 */
	public static function merge_duplicates_by_key( array $array, string $key ): array {
		$seen   = [];
		$result = [];

		foreach ( $array as $item ) {
			if ( ! isset( $item[ $key ] ) ) {
				continue;
			}
			$key_value = $item[ $key ];
			if ( ! isset( $seen[ $key_value ] ) ) {
				$seen[ $key_value ] = true;
				$result[]           = $item;
			} else {
				// Merge the item with the existing item in the result array.
				$result[ $key_value ] = array_merge( $item, $result[ $key_value ] );
			}
		}

		return $result;
	}
}
