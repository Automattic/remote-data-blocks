<?php declare(strict_types = 1);

namespace RemoteDataBlocks\HttpClient;

final class CacheKeyRequestHeaders {
	public const DEFAULT_HEADERS = [ 'Authorization', 'Cache-Control' ];

	/**
	 * Merge request header name lists without case-insensitive duplicates.
	 *
	 * @param array<string> ...$header_lists Request header name lists.
	 * @return array<string> Merged request header names.
	 */
	public static function merge( array ...$header_lists ): array {
		$merged_headers = [];
		$seen_headers = [];

		foreach ( $header_lists as $header_list ) {
			foreach ( $header_list as $header ) {
				$normalized_header = strtolower( $header );
				if ( isset( $seen_headers[ $normalized_header ] ) ) {
					continue;
				}

				$seen_headers[ $normalized_header ] = true;
				$merged_headers[] = $header;
			}
		}

		return $merged_headers;
	}
}
