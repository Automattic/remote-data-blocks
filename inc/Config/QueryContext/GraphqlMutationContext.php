<?php

/**
 * GraphqlMutationContext class
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
abstract class GraphqlMutationContext extends HttpQueryContext {

	/**
	 * Override this method to define a custom request method for this mutation.
	 */
	public function get_request_method(): string {
		return 'POST';
	}

	/**
	 * Define this method to provide the GraphQL mutation document.
	 *
	 * @return string The GraphQL mutation document.
	 */
	abstract public function get_mutation(): string;

	/**
	 * Override this method to define the GraphQL mutation variables.
	 *
	 * @return array The GraphQL query variables.
	 */
	public function get_mutation_variables( array $input_variables ): array {
		return $input_variables;
	}

	/**
	 * Convert the mutation and variables into a GraphQL request body.
	 */
	public function get_request_body( array $input_variables ): array {
		$variables = $this->get_mutation_variables( $input_variables );

		return [
			'query'     => $this->get_mutation(),
			'variables' => empty( $variables ) ? null : $variables,
		];
	}

	/**
	 * GraphQL mutations are uncachable by default.
	 */
	public function get_cache_ttl( array $input_variables ): int {
		return -1;
	}
}
