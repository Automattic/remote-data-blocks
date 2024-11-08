<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\GitHub;

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

		// Convert the HTML to UTF-8 if it's not already
		$html = mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' );

		// Suppress errors due to malformed HTML
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
				$newHref = self::adjust_path( $href );

				// Set the new href
				$node->setAttribute( 'href', $newHref );
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
		$parts = parse_url( $path );

		// Extract the path and fragment
		$originalPath = isset( $parts['path'] ) ? $parts['path'] : '';
		$fragment = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';

		// Remove leading './' or '/' but not '../'
		$adjustedPath = preg_replace( '#^(\./|/)+#', '', $originalPath );

		// Prepend '../' to go one level up
		$adjustedPath = '../' . $adjustedPath;

		// Reconstruct the path with fragment
		return $adjustedPath . $fragment;
	}
}
