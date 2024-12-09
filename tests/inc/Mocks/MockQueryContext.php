<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Tests\Mocks\MockDataSource;
use RemoteDataBlocks\Tests\Mocks\MockValidator;
use stdClass;
use WP_Error;

class MockQueryContext extends HttpQueryContext {
	private array|null $response_data = null;

	public static function create( array $config = [] ): static|WP_Error {
		return parent::from_array( [
			'data_source' => MockDataSource::create(),
			'display_name' => 'Mock Query',
			'input_schema' => $config['input_schema'] ?? [],
			'output_schema' => $config['output_schema'] ?? [],
			'query_key' => 'mock_query',
			'query_runner' => $config['query_runner'] ?? new MockQueryRunner(),
		], new MockValidator() );
	}

	public function preprocess_response( array $response_data, array $input_variables ): array {
		if ( null !== $this->response_data ) {
			return $this->response_data;
		}

		return $response_data;
	}

	public function set_output_schema( array $output_schema ): void {
		$this->config['output_schema'] = $output_schema;
	}

	public function set_request_method( string $method ): void {
		$this->config['request_method'] = $method;
	}

	public function set_request_body( array $body ): void {
		$this->config['request_body'] = $body;
	}

	public function set_response_data( array|stdClass $data ): void {
		$this->response_data = $data;
	}
}
