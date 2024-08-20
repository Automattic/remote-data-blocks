<?php

/**
 * HttpDatasource class
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */

namespace RemoteDataBlocks\Config;

defined( 'ABSPATH' ) || exit();

/**
 * Base class used to define a Remote Data Blocks Datasource for an HTTP API. It
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
 * Only extend this class if you have custom datasources not provided by VIP.
 */
abstract class HttpDatasource implements HttpDatasourceConfig {

	/**
	 * Get the endpoint for the query. Note that the query configuration has an
	 * opportunity to change / override the endpoint at request time. For REST
	 * APIs, a useful pattern is for the datasource to define a base endpoint and
	 * the query config to target a specific resource.
	 *
	 * @return string The endpoint for the query.
	 */
	abstract public function get_endpoint(): string;

	/**
	 * Get the request headers. Override this method to provide authorization or
	 * other custom request headers. Note that the query configuration can override
	 * or extend these headers at request time.
	 *
	 * @return array Associative array of request headers.
	 */
	abstract public function get_request_headers(): array;
}
