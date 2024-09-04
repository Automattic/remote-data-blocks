<?php

namespace RemoteDataBlocks\Config;

defined( 'ABSPATH' ) || exit();

/**
 * HttpDatasource class
 *
 * Implements the HttpDatasourceInterface to define a generic HTTP datasource.
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */
class HttpDatasource implements HttpDatasourceInterface {

	/**
	 * Configuration object for this HTTP datasource.
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Constructor for the HttpDatasource.
	 *
	 * @param array $config The configuration object for this HTTP datasource.
	 */
	public function __construct( array $config ) {
		$this->config = $config;
	}

	/**
	 * Get a human-readable name for this datasource.
	 *
	 * @return string The friendly name of the datasource.
	 */
	public function get_friendly_name(): string {
		return $this->config['friendly_name'];
	}

	/**
	 * Get a unique identifier for this datasource.
	 *
	 * @return string The unique identifier of the datasource.
	 */
	public function get_uid(): string {
		return hash( 'sha256', $this->config['uid'] );
	}

	/**
	 * Get the endpoint for the query.
	 *
	 * @return string The endpoint for the query.
	 */
	public function get_endpoint(): string {
		return $this->config['endpoint'];
	}

	/**
	 * Get the request headers.
	 *
	 * @return array Associative array of request headers.
	 */
	public function get_request_headers(): array {
		return $this->config['request_headers'] ?? [];
	}

	/**
	 * Get the optional image URL for the datasource.
	 *
	 * @return string|null The image URL or null if not set.
	 */
	public function get_image_url(): ?string {
		return $this->config['image_url'] ?? null;
	}
}