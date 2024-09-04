<?php

/**
 * HttpDatasourceInterface
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */

namespace RemoteDataBlocks\Config;

defined( 'ABSPATH' ) || exit();

/**
 * Interface used to define a Remote Data Blocks Datasource for an HTTP API. It
 * defines the properties of an API that will be shared by queries against that
 * API.
 *
 * Assumptions:
 * - The API speaks valid JSON, both for request and response bodies.
 * - The API returns 2XX for successful requests.
 * - The API returns 3XX for redirects with a maximum of 5 redirects.
 * - The API returns 4XX or 5XX for unrecoverable errors, in which case the
 *   response should be ignored.
 *
 * If you are a WPVIP customer, datasources are automatically provided by VIP.
 * Only implement this interface if you have custom datasources not provided by VIP.
 */
interface HttpDatasourceInterface {

	/**
	 * Constructor for the HttpDatasource.
	 *
	 * @param array $config The configuration object for this HTTP datasource.
	 */
	public function __construct( array $config );

	/**
	 * Get a human-readable name for this datasource.
	 *
	 * This method should return a friendly, descriptive name for the datasource
	 * that can be used in user interfaces or for identification purposes.
	 *
	 * @return string The friendly name of the datasource.
	 */
	public function get_friendly_name(): string;

	/**
	 * Get a unique identifier for this datasource.
	 *
	 * This method should return a sha256 hash of a globally unique identifier
	 * for the datasource's configuration that can be used to identify the
	 * datasource authoritatively.
	 *
	 * @return string sha256 hash of a globally unique identifier of the datasource.
	 */
	public function get_uid(): string;

	/**
	 * Get the endpoint for the query. Note that the query configuration has an
	 * opportunity to change / override the endpoint at request time. For REST
	 * APIs, a useful pattern is for the datasource to define a base endpoint and
	 * the query config to target a specific resource.
	 *
	 * @return string The endpoint for the query.
	 */
	public function get_endpoint(): string;

	/**
	 * Get the request headers. Override this method to provide authorization or
	 * other custom request headers. Note that the query configuration can override
	 * or extend these headers at request time.
	 *
	 * @return array Associative array of request headers.
	 */
	public function get_request_headers(): array;

	/**
	 * An optional image URL that can represent the datasource in the block editor
	 * (e.g., in modals or in the block inspector).
	 *
	 * @return string|null The image URL or null if not set.
	 */
	public function get_image_url(): ?string;
}
