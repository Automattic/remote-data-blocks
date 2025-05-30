<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

use RemoteDataBlocks\Config\Query\HttpQueryInterface;
use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use WP_Error;

class MockQueryRunner extends QueryRunner {
	/** @var array|WP_Error|null */
	protected array|WP_Error|null $query_results = null;

	/** @var array<mixed> */
	private array $execute_call_inputs = [];

	public function setResults( array|WP_Error $results ): void {
		if ( $results instanceof WP_Error ) {
			$this->query_results = $results;
			return;
		}

		$this->query_results = [
			'is_collection' => false,
			'results' => array_map( function ( $result ): array {
				return [
					'result' => $result,
				];
			}, $results ),
		];
	}

	public function execute( HttpQueryInterface $query, array $input_variables ): array|WP_Error {
		array_push( $this->execute_call_inputs, $input_variables );
		return $this->query_results ?? new WP_Error( 'no-results', 'No results available.' );
	}

	public function getLastExecuteCallInput(): array|null {
		return end( $this->execute_call_inputs ) ?? null;
	}
}
