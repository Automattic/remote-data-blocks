<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

use RemoteDataBlocks\Config\Query\HttpQueryInterface;
use RemoteDataBlocks\Config\QueryRunner\QueryRunnerInterface;
use WP_Error;

class MockQueryRunner implements QueryRunnerInterface {
	/** @var array<array|WP_Error> */
	private array $query_results = [];

	/** @var array<mixed> */
	private array $execute_call_inputs = [];

	public function addResult( string $field, mixed $result ): void {
		if ( $result instanceof WP_Error ) {
			array_push( $this->query_results, $result );
			return;
		}

		array_push( $this->query_results, [
			'is_collection' => false,
			'results' => [
				[
					'result' => [
						$field => [ 'value' => $result ],
					],
				],
			],
		] );
	}

	public function execute( HttpQueryInterface $query, array $input_variables ): array|WP_Error {
		array_push( $this->execute_call_inputs, $input_variables );
		return array_shift( $this->query_results ) ?? new WP_Error( 'no-results', 'No results available.' );
	}

	public function getLastExecuteCallInput(): array|null {
		return end( $this->execute_call_inputs ) ?? null;
	}
}
