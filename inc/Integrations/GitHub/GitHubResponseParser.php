<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\GitHub;

/**
 * @psalm-api
 */
class GitHubResponseParser {
	/**
	 * Updates the relative/absolute links in href attributes.
	 * This adjusts the links so they work correctly when the file structure changes.
	 * - All relative paths go one level up.
	 * - All absolute paths are converted to relative paths one level up.
	 * - Handles URLs with fragment identifiers (e.g., '#section').
	 *
	 * @param string $html The HTML response data.
	 * @return string The updated HTML response data.
	 */
	public static function update_html_links( string $html ): string {
		// Load the HTML into a DOMDocument
		$dom = new \DOMDocument();

		// Convert HTML to UTF-8 using htmlspecialchars instead of mb_convert_encoding
		$html = '<?xml encoding="UTF-8">' . $html;
		
		// Suppress errors due to malformed HTML
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@$dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		// Create an XPath to query href attributes
		$xpath = new \DOMXPath( $dom );

		// Query all elements with href attributes
		$nodes = $xpath->query( '//*[@href]' );
		foreach ( $nodes as $node ) {
			if ( ! $node instanceof \DOMElement ) {
				continue;
			}
			$href = $node->getAttribute( 'href' );

			// Check if the href is non-empty and points to a markdown file 
			if ( $href && preg_match( '/\.md($|#)/', $href ) ) {
				// Adjust the path
				$new_href = self::adjust_path( $href );

				// Set the new href
				$node->setAttribute( 'href', $new_href );
			}
		}

		// Save and return the updated HTML
		return $dom->saveHTML();
	}

	/**
	 * Adjusts the given path by going one level up.
	 * Preserves fragment identifiers (anchors) in the URL.
	 *
	 * @param string $path The original path.
	 * @return string The adjusted path.
	 */
	private static function adjust_path( string $path ): string {
		// Parse the URL to separate the path and fragment
		$parts = wp_parse_url( $path );

		// Extract the path and fragment
		$original_path = isset( $parts['path'] ) ? $parts['path'] : '';
		$fragment = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';

		// Remove leading './' or '/' but not '../'
		$adjusted_path = preg_replace( '#^(\./|/)+#', '', $original_path );

		// Prepend '../' to go one level up
		$adjusted_path = '../' . $adjusted_path;

		// Reconstruct the path with fragment
		return $adjusted_path . $fragment;
	}
}
