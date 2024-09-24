<?php

/**
 * GraphqlQueryContext class
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */

namespace RemoteDataBlocks\Config\QueryContext;

defined( 'ABSPATH' ) || exit();

/**
 * Base class used to define a Remote Data Query. This class defines a
 * composable query that allows it to be composed with another query or a block.
 */
abstract class GraphqlQueryContext extends HttpQueryContext {

	/**
	 * Override this method to define a custom request method for this query.
	 */
	public function get_request_method(): string {
		return 'POST';
	}

	/**
	 * Define this method to provide the GraphQL query document.
	 *
	 * @return string The GraphQL query document.
	 */
	abstract public function get_query(): string;

	/**
	 * Override this method to define the GraphQL query variables.
	 *
	 * @return array The GraphQL query variables.
	 */
	public function get_query_variables( array $input_variables ): array {
		return $input_variables;
	}

	/**
	 * Convert the query and variables into a GraphQL request body.
	 */
	public function get_request_body( array $input_variables ): array {
		$variables = $this->get_query_variables( $input_variables );

		return [
			'query'     => $this->get_query(),
			'variables' => empty( $variables ) ? null : $variables,
		];
	}

	/**
	 * Override this method to define the cache object TTL for this query. Return
	 * -1 to disable caching. Return null to use the default cache TTL.
	 *
	 * @return int|null The cache object TTL in seconds.
	 */
	public function get_cache_ttl( array $input_variables ): null {
		// Use default cache TTL.
		return null;
	}
}
