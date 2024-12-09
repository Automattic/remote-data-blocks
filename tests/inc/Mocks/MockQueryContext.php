<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Tests\Mocks\MockDataSource;
use RemoteDataBlocks\Tests\Mocks\MockValidator;
use WP_Error;

class MockQueryContext extends HttpQueryContext {
	public function execute( array $input_variables ): array|WP_Error {
		$query_runner = $this->config['query_runner'] ?? new MockQueryRunner();
		return $query_runner->execute( $this, $input_variables );
	}

	public static function create( array $config ): static|WP_Error {
		return parent::from_array( [
			'data_source' => MockDataSource::from_array( MockDataSource::MOCK_CONFIG, new MockValidator() ),
			'display_name' => 'Mock Query',
			'input_schema' => $config['input_schema'] ?? [],
			'output_schema' => $config['output_schema'] ?? [],
			'query_key' => 'mock_query',
		] );
	}
}
