<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Utils;

class Utils {
	/**
	 * Removes duplicates values from an array of arrays bases on the specified key.
	 *
	 * @param array $array The array of arrays to remove duplicates from.
	 * @param string $key The key to remove duplicates based on.
	 * @return array The array with duplicates removed.
	 */
	public static function remove_duplicates_by_key( array $array, string $key ): array {
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
			}
		}

		return $result;
	}
}
