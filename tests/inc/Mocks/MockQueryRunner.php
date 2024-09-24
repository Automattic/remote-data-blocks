<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

use RemoteDataBlocks\Config\QueryRunner\QueryRunnerInterface;

class MockQueryRunner implements QueryRunnerInterface {
	private $query_results = [];

	public function addResult( $field, $result ) {
		if ( $result instanceof \WP_Error ) {
			array_push( $this->query_results, $result );
			return;
		}

		array_push( $this->query_results, [
			'is_collection' => false,
			'results'       => [
				[
					'result' => [
						$field => [ 'value' => $result ],
					],
				],
			],
		] );
	}

	public function execute( array $input_variables ): array|\WP_Error {
		return array_shift( $this->query_results );
	}
}
