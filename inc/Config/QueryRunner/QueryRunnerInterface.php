<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\QueryRunner;

use RemoteDataBlocks\Config\Query\HttpQueryInterface;
use WP_Error;

interface QueryRunnerInterface {
	/**
	 * Execute the query and return processed results.
	 *
	 * @param HttpQueryInterface $query The query to execute.
	 * @param array<array-key, mixed> $input_variables The input variables for the current request.
	 * @return WP_Error|array{
	 *   is_collection: bool,
	 *   metadata: array<string, array{
	 *     name: string,
	 *     type: string,
	 *     value: string|int|null,
	 *   }>,
	 *   pagination: array{
	 *     total_items: int|null,
	 *     next_input_variables: array<array-key, mixed>|null,
	 *     previous_input_variables: array<array-key, mixed>|null,
	 *   },
	 *   results: null|array<int, array{
	 *     result: array{
	 *       name: string,
	 *       type: string,
	 *       value: string,
	 *     },
	 *   }>,
	 * }
	 */
	public function execute( HttpQueryInterface $query, array $input_variables ): array|WP_Error;
}
