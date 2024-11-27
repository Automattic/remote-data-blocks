<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Tests\Mocks\MockDataSource;
use RemoteDataBlocks\Tests\Mocks\MockValidator;
use RemoteDataBlocks\Config\QueryRunner\QueryRunnerInterface;
use WP_Error;

class MockQueryContext extends HttpQueryContext {
	public function __construct(
		private QueryRunnerInterface $mock_qr,
		public array $input_schema = [],
		public array $output_schema = []
	) {
		parent::__construct(
			MockDataSource::from_array( MockDataSource::MOCK_CONFIG, new MockValidator() ),
			$input_schema,
			$output_schema,
		);
	}

	public function execute( array $input_variables ): array|WP_Error {
		return $this->mock_qr->execute( $input_variables );
	}
}
