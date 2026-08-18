<?php declare(strict_types = 1);

namespace RemoteDataBlocks\HttpClient;

final class CacheKeyRequestHeaders {
	private const DEFAULT_HEADERS = [ 'Authorization', 'Cache-Control' ];

	/**
	 * Merge the given header list with DEFAULT_HEADERS, removing case-insensitive duplicates.
	 *
	 * @param array<string> $headers Request header names to merge with DEFAULT_HEADERS.
	 * @return array<string> Merged request header names.
	 */
	public static function merge( array $headers ): array {
		# Start with DEFAULT_HEADERS
		$seen_headers = array_fill_keys(
			array_map( 'strtolower', self::DEFAULT_HEADERS ),
			true
		);

		# Add extra headers, skipping any duplicates (case-insensitive)
		$seen_headers = array_merge(
			$seen_headers,
			array_fill_keys( array_map( 'strtolower', $headers ), true )
		);

		# Return the unique header keys
		return array_keys( $seen_headers );
	}
}
