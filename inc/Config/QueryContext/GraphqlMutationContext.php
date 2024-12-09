<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\QueryContext;

defined( 'ABSPATH' ) || exit();

class GraphqlMutationContext extends GraphqlQueryContext {
	/**
	 * GraphQL mutations are uncachable by default.
	 */
	public function get_cache_ttl( array $input_variables ): int {
		return -1;
	}
}
