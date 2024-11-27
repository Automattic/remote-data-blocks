<?php declare(strict_types = 1);

namespace RemoteDataBlocks\ExampleApi\Queries;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use WP_Error;

// TODO delete
class ExampleApiQuery extends HttpQueryContext {
	public function execute( array $input_variables ): array|WP_Error {
		$query_runner = new ExampleApiQueryRunner( $this );
		return $query_runner->execute( $input_variables );
	}
}
