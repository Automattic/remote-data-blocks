<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\GitHub;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Integrations\GitHub\GitHubDataSource;

class GitHubGetFileAsHtmlQuery extends HttpQueryContext {
	/**
	 * @inheritDoc
	 * @param string|null $default_file_extension Optional file extension to append if missing (e.g., '.md')
	 */
	public function __construct(
		private HttpDataSource $data_source,
		private ?string $default_file_extension = null
	) {
		parent::__construct( $data_source );
	}

	private function ensure_file_extension( string $file_path ): string {
		if ( ! $this->default_file_extension ) {
			return $file_path;
		}

		return str_ends_with( $file_path, $this->default_file_extension ) ? $file_path : $file_path . $this->default_file_extension;
	}

	public function get_input_schema(): array {
		return [
			'file_path' => [
				'name' => 'File Path',
				'type' => 'string',
				'overrides' => [
					[
						'target' => 'utm_content',
						'type' => 'url',
					],
				],
				'transform' => function ( array $data ): string {
					return $this->ensure_file_extension( $data['file_path'] );
				},
			],
		];
	}

	public function get_output_schema(): array {
		return [
			'is_collection' => false,
			'mappings' => [
				'file_content' => [
					'name' => 'File Content',
					'path' => '$.content',
					'type' => 'html',
				],
				'file_path' => [
					'name' => 'File Path',
					'path' => '$.path',
					'type' => 'string',
				],
			],
		];
	}

	public function get_endpoint( array $input_variables ): string {
		/** @var GitHubDataSource $data_source */
		$data_source = $this->get_data_source();

		return sprintf(
			'https://api.github.com/repos/%s/%s/contents/%s?ref=%s',
			$data_source->get_repo_owner(),
			$data_source->get_repo_name(),
			$input_variables['file_path'],
			$data_source->get_ref()
		);
	}

	public function get_request_headers( array $input_variables ): array {
		return [
			'Accept' => 'application/vnd.github.html+json',
		];
	}

	public function process_response( string $html_response_data, array $input_variables ): array {
		$content = $html_response_data;
		if ( '.md' === $this->default_file_extension ) {
			$content = $this->update_markdown_links( $content );
		}

		return [
			'content' => $content,
			'file_path' => $input_variables['file_path'],
		];
	}

	/**
	 * Updates the relative/absolute markdown links in href attributes.
	 * This adjusts the links so they work correctly when the file structure changes.
	 * - All relative paths go one level up.
	 * - All absolute paths are converted to relative paths one level up.
	 * - Handles URLs with fragment identifiers (e.g., '#section').
	 * - Removes the '.md' extension from the paths.
	 * @param string $html The HTML response data.
	 * @return string The updated HTML response data.
	 */
	private function update_markdown_links( string $html ): string {
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
				$new_href = $this->adjust_markdown_file_path( $href );

				// Set the new href
				$node->setAttribute( 'href', $new_href );
			}
		}

		// Save and return the updated HTML
		return $dom->saveHTML();
	}

	/**
	 * Adjusts the given path by going one level up and removes the '.md' extension.
	 * Preserves fragment identifiers (anchors) in the URL.
	 *
	 * @param string $path The original path.
	 * @return string The adjusted path.
	 */
	private function adjust_markdown_file_path( string $path ): string {
		// Parse the URL to separate the path and fragment
		$parts = wp_parse_url( $path );

		// Extract the path and fragment
		$original_path = isset( $parts['path'] ) ? $parts['path'] : '';
		$fragment = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';

		// Remove leading './' or '/' but not '../'
		$adjusted_path = preg_replace( '#^(\./|/)+#', '', $original_path );

		// Prepend '../' to go one level up
		$adjusted_path = '../' . $adjusted_path;

		// Remove the '.md' extension
		$adjusted_path = preg_replace( '/\.md$/', '', $adjusted_path );

		// Reconstruct the path with fragment
		return $adjusted_path . $fragment;
	}
}
