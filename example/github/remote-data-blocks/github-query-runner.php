<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\GitHub;

use RemoteDataBlocks\Config\Query\HttpQueryInterface;
use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use DOMElement;
use DOMXPath;
use WP_Error;

defined( 'ABSPATH' ) || exit();

/**
 * Custom query runner that process custom processing for GitHub API responses
 * that return HTML / Markdown instead of JSON. This also provides custom
 * processing to adjust embedded links.
 *
 * Data fetching and caching is still delegated to the parent QueryRunner class.
 */
class GitHubQueryRunner extends QueryRunner {
	private string $default_file_extension = '.md';

	public function execute( HttpQueryInterface $query, array $input_variables ): array|WP_Error {
		$input_variables['file_path'] = $this->ensure_file_extension( $input_variables['file_path'] );

		return parent::execute( $query, $input_variables );
	}

	/**
	 * @inheritDoc
	 *
	 * The API response is raw HTML, so we return an object construct containing
	 * the HTML as a property.
	 */
	protected function deserialize_response( string $raw_response_data, array $input_variables ): array {
		return [
			'content' => $raw_response_data,
			'path' => $input_variables['file_path'],
		];
	}

	private function ensure_file_extension( string $file_path ): string {
		return str_ends_with( $file_path, $this->default_file_extension ) ? $file_path : $file_path . $this->default_file_extension;
	}

	public static function generate_file_content( array $response_data ): string {
		$file_content = $response_data['content'] ?? '';
		$file_path = $response_data['file_path'] ?? '';

		return self::update_markdown_links( $file_content, $file_path );
	}

	/**
	 * Updates the relative/absolute markdown links in href attributes.
	 * This adjusts the links so they work correctly when the file structure changes.
	 * - All relative paths go one level up.
	 * - All absolute paths are converted to relative paths one level up.
	 * - Handles URLs with fragment identifiers (e.g., '#section').
	 * - Removes the '.md' extension from the paths.
	 * @param string $html The HTML response data.
	 * @param string $current_file_path The current file's path.
	 * @return string The updated HTML response data.
	 */
	private static function update_markdown_links( string $html, string $current_file_path = '' ): string {
		// Load the HTML into a DOMDocument
		$dom = new \DOMDocument();

		// Convert HTML to UTF-8 using htmlspecialchars instead of mb_convert_encoding
		$html = '<?xml encoding="UTF-8">' . $html;

		// Suppress errors due to malformed HTML
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@$dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		// Create an XPath to query href attributes
		$xpath = new DOMXPath( $dom );

		// Query all elements with href attributes
		$nodes = $xpath->query( '//*[@href]' );
		foreach ( $nodes as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}
			$href = $node->getAttribute( 'href' );

			// Check if the href is non-empty, points to a markdown file, and is a local path
			if ( $href &&
				preg_match( '/\.md($|#)/', $href ) &&
				! preg_match( '/^(https?:)?\/\//', $href )
			) {
				// Adjust the path
				$new_href = self::adjust_markdown_file_path( $href, $current_file_path );

				// Set the new href
				$node->setAttribute( 'href', $new_href );
			}
		}

		// Save and return the updated HTML
		return $dom->saveHTML();
	}

	/**
	 * Adjusts the markdown file path by resolving relative paths to absolute paths.
	 * Preserves fragment identifiers (anchors) in the URL.
	 *
	 * @param string $path The original path.
	 * @param string $current_file_path The current file's path.
	 * @return string The adjusted path.
	 */
	private static function adjust_markdown_file_path( string $path, string $current_file_path = '' ): string {
		global $post;
		$page_slug = $post->post_name;

		// Parse the URL to separate the path and fragment
		$parts = wp_parse_url( $path );

		// Extract the path and fragment
		$original_path = isset( $parts['path'] ) ? $parts['path'] : '';
		$fragment = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';

		// Get the directory of the current file
		$current_dir = dirname( $current_file_path );

		// Resolve the absolute path based on the current directory
		if ( str_starts_with( $original_path, '/' ) ) {
			// Already an absolute path from root, just remove leading slash
			$absolute_path = ltrim( $original_path, '/' );
		} else {
			// Use realpath to resolve relative paths
			$temp_path = $current_dir . '/' . $original_path;
			$parts = explode( '/', $temp_path );
			$absolute_parts = [];

			foreach ( $parts as $part ) {
				if ( '.' === $part || '' === $part ) {
					continue;
				}
				if ( '..' === $part ) {
					array_pop( $absolute_parts );
				} else {
					$absolute_parts[] = $part;
				}
			}

			$absolute_path = implode( '/', $absolute_parts );
		}

		// Remove the .md extension
		$absolute_path = preg_replace( '/\.md$/', '', $absolute_path );

		// Ensure the path starts with a forward slash and includes the page slug
		return '/' . $page_slug . '/' . $absolute_path . $fragment;
	}
}
