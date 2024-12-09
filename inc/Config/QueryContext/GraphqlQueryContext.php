<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\QueryContext;

use RemoteDataBlocks\Validation\ConfigSchemas;

defined( 'ABSPATH' ) || exit();

/**
 * GraphqlQueryContext class
 *
 * Base class used to define a Remote Data Query. This class defines a
 * composable query that allows it to be composed with another query or a block.
 *
 */
class GraphqlQueryContext extends HttpQueryContext {
	public function get_request_method(): string {
		return $this->config['request_method'] ?? 'POST';
	}

	/**
	 * Convert the query and variables into a GraphQL request body.
	 */
	public function get_request_body( array $input_variables ): array {
		$variables = $this->get_or_call_from_config( 'graphql_query_variables', $input_variables ) ?? [];

		return [
			'query' => $this->config['graphql_query'],
			'variables' => empty( $variables ) ? null : $variables,
		];
	}

	/**
	 * @inheritDoc
	 */
	protected static function get_config_schema(): array {
		return ConfigSchemas::get_graphql_query_config_schema();
	}
}
