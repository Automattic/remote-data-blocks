<?php declare(strict_types = 1);

use RemoteDataBlocks\Config\ArraySerializable;
use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Config\Query\HttpQueryInterface;
use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use RemoteDataBlocks\Editor\BlockManagement\BlockRegistration;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;

class RDBTestCase extends WP_UnitTestCase {
	protected function register_mocked_data_block( string $block_title, array $api_response, array $output_schema ): void {
		$test_query = $this->generate_query( $api_response, $output_schema );

		$registration_result = register_remote_data_block( [
			'title' => $block_title,
			'render_query' => [
				'query' => $test_query,
			],
		] );

		$this->assertTrue( $registration_result );

		// Register block configuration with WordPress, normally done during the 'init' filter
		$block_config = ConfigStore::get_block_configuration( ConfigStore::get_block_name( $block_title ) );
		$this->assertTrue( is_array( $block_config ) && [] !== $block_config );
		BlockRegistration::register_block_configuration( $block_config );
	}

	protected function generate_query( array $api_response, array $output_schema ): ArraySerializable|WP_Error {
		$test_query_runner = $this->get_query_runner_with_response( $api_response );

		$test_data_source = HttpDataSource::from_array( [
			'service_config' => [
				'__version' => 1,
				'display_name' => 'Test API',

				// Mocked query runner will not actually make a request to the endpoint URL.
				'endpoint' => 'https://example.com/not-a-real-api',
			],
		] );

		$test_query = HttpQuery::from_array( [
			'data_source' => $test_data_source,
			'query_runner' => $test_query_runner,
			'output_schema' => $output_schema,
		] );

		return $test_query;
	}

	protected function get_query_runner_with_response( array $response_data ): QueryRunner {
		return new class($response_data) extends QueryRunner {
			private $response_data;

			public function __construct( array $response_data ) {
				$this->response_data = $response_data;
			}

			protected function get_raw_response_data( HttpQueryInterface $query, array $input_variables ): array|WP_Error {
				return [
					'metadata' => [
						'age' => 100,
						'status_code' => 200,
					],
					'response_data' => $this->response_data,
				];
			}
		};
	}
}
