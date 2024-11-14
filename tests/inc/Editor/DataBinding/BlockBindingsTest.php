<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Editor\DataBinding;

use RemoteDataBlocks\Tests\Mocks\MockWordPressFunctions;

/**
 * Mock the global WordPress functions in implementation namespace.
 */
function get_query_var( string $var, $default = '' ): mixed {
	return MockWordPressFunctions::get_query_var( $var, $default );
}

// phpcs:disable Universal.Namespaces.OneDeclarationPerFile.MultipleFound
namespace RemoteDataBlocks\Tests\Editor\DataBinding;

use PHPUnit\Framework\TestCase;
use Mockery;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\Editor\DataBinding\BlockBindings;
use RemoteDataBlocks\Tests\Mocks\MockQueryRunner;
use RemoteDataBlocks\Tests\Mocks\MockWordPressFunctions;
use RemoteDataBlocks\Tests\Mocks\MockQueryContext;

class BlockBindingsTest extends TestCase {
	private const MOCK_BLOCK_NAME = 'test/block';
	private const MOCK_OPERATION_NAME = 'test-operation';

	private const MOCK_INPUT_SCHEMA = [
		'test_input_field' => [
			'name' => 'Test Input Field',
			'type' => 'string',
		],
	];

	private const MOCK_OUTPUT_SCHEMA = [
		'is_collection' => false,
		'mappings' => [
			'output_field' => [
				'name' => 'Output Field',
				'type' => 'string',
				'path' => '$.output_field',
			],
		],
	];
	private const MOCK_OUTPUT_FIELD_NAME = 'output_field';
	private const MOCK_OUTPUT_FIELD_VALUE = 'Test Output Value';
	private const MOCK_OUTPUT_QUERY_RESULTS = [
		'is_collection' => false,
		'results' => [
			[
				'result' => [
					self::MOCK_OUTPUT_FIELD_NAME => [
						'value' => self::MOCK_OUTPUT_FIELD_VALUE,
					],
				],
			],
		],
	];

	protected function tearDown(): void {
		parent::tearDown();
		Mockery::close();
		MockWordPressFunctions::reset();
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_execute_query_with_no_config(): void {
		/**
		 * Mock the ConfigStore to return null.
		 */
		$mock_config_store = Mockery::namedMock( ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_configuration' )
			->once()
			->with( self::MOCK_BLOCK_NAME )
			->andReturn( null );

		$block_context = [
			'blockName' => self::MOCK_BLOCK_NAME,
			'queryInput' => [],
		];

		$query_results = BlockBindings::execute_query( $block_context, 'test-operation' );

		/**
		 * Assert that the query results are null as no configuration was found.
		 */
		$this->assertNull( $query_results );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_execute_query_returns_query_results(): void {
		/**
		 * Mock the QueryRunner to return a result.
		 */
		$mock_qr = new MockQueryRunner();
		$mock_qr->addResult( 'output_field', 'Test Output Value' );

		$block_context = [
			'blockName' => self::MOCK_BLOCK_NAME,
			'queryInput' => [
				'test_input_field' => 'test_value',
			],
		];

		$mock_block_config = [
			'queries' => [
				'__DISPLAY__' => new MockQueryContext(
					$mock_qr,
					self::MOCK_INPUT_SCHEMA,
					self::MOCK_OUTPUT_SCHEMA,
				),
			],
		];

		/**
		 * Mock the ConfigStore to return the block configuration.
		 */
		$mock_config_store = Mockery::namedMock( ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_configuration' )
			->once()
			->with( self::MOCK_BLOCK_NAME )
			->andReturn( $mock_block_config );

		$query_results = BlockBindings::execute_query( $block_context, self::MOCK_OPERATION_NAME );
		$this->assertSame( $query_results, self::MOCK_OUTPUT_QUERY_RESULTS );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_execute_query_with_overrides(): void {
		/**
		 * Set the query var to an override value.
		 */
		MockWordPressFunctions::set_query_var( 'test_input_field', 'override_value' );

		/**
		 * Mock the QueryRunner to return a result.
		 */
		$mock_qr = new MockQueryRunner();
		$mock_qr->addResult( self::MOCK_OUTPUT_FIELD_NAME, self::MOCK_OUTPUT_FIELD_VALUE );

		$block_context = [
			'blockName' => self::MOCK_BLOCK_NAME,
			'queryInput' => [
				'test_input_field' => 'test_value',
			],
			'queryInputOverrides' => [
				'test_input_field' => [
					'type' => 'url',
					'display' => '/test_input_field/{test_input_field}',
				],
			],
		];

		$input_schema = [
			'test_input_field' => [
				'name' => 'Test Input Field',
				'type' => 'string',
				'overrides' => [
					[
						'target' => 'test_target',
						'type' => 'url',
					],
				],
			],
		];

		$mock_block_config = [
			'queries' => [
				'__DISPLAY__' => new MockQueryContext(
					$mock_qr,
					$input_schema,
					self::MOCK_OUTPUT_SCHEMA,
				),
			],
		];

		$mock_config_store = Mockery::namedMock( ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_configuration' )
			->once()
			->with( self::MOCK_BLOCK_NAME )
			->andReturn( $mock_block_config );

		$query_results = BlockBindings::execute_query( $block_context, self::MOCK_OPERATION_NAME );

		$this->assertSame( $query_results, self::MOCK_OUTPUT_QUERY_RESULTS );

		/**
		 * Assert that the query runner received the correct input after overrides were applied.
		 */
		$this->assertSame( $mock_qr->getLastExecuteCallInput(), [
			'test_input_field' => 'override_value',
		] );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_execute_query_with_query_input_transformations(): void {
		/**
		 * Mock the QueryRunner to return a result.
		 */
		$mock_qr = new MockQueryRunner();
		$mock_qr->addResult( 'output_field', 'Test Output Value' );

		$block_context = [
			'blockName' => self::MOCK_BLOCK_NAME,
			'queryInput' => [
				'test_input_field' => 'test_value',
			],
		];

		$input_schema = [
			'test_input_field' => [
				'name' => 'Test Input Field',
				'type' => 'string',
				'generate' => function ( array $data ): string {
					return $data['test_input_field'] . ' transformed';
				},
			],
		];

		$mock_block_config = [
			'queries' => [
				'__DISPLAY__' => new MockQueryContext(
					$mock_qr,
					$input_schema,
					self::MOCK_OUTPUT_SCHEMA,
				),
			],
		];

		$mock_config_store = Mockery::namedMock( ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_configuration' )
			->once()
			->with( self::MOCK_BLOCK_NAME )
			->andReturn( $mock_block_config );

		$query_results = BlockBindings::execute_query( $block_context, self::MOCK_OPERATION_NAME );
		$this->assertSame( $query_results, self::MOCK_OUTPUT_QUERY_RESULTS );

		/**
		 * Assert that the query runner received the correct input after transformations were applied.
		 */
		$this->assertSame( $mock_qr->getLastExecuteCallInput(), [
			'test_input_field' => 'test_value transformed',
		] );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_execute_query_with_query_input_transformed_with_multiple_inputs(): void {
		/**
		 * Mock the QueryRunner to return a result.
		 */
		$mock_qr = new MockQueryRunner();
		$mock_qr->addResult( 'output_field', 'Test Output Value' );

		$block_context = [
			'blockName' => self::MOCK_BLOCK_NAME,
			'queryInput' => [
				'test_input_field' => 'test_value',
				'another_input_field' => 'another_value',
			],
		];

		$input_schema = [
			'test_input_field' => [
				'name' => 'Test Input Field',
				'type' => 'string',
				'generate' => function ( array $data ): string {
					return $data['test_input_field'] . ' ' . $data['another_input_field'];
				},
			],
			'another_input_field' => [
				'name' => 'Another Input Field',
				'type' => 'string',
			],
		];

		$mock_block_config = [
			'queries' => [
				'__DISPLAY__' => new MockQueryContext(
					$mock_qr,
					$input_schema,
					self::MOCK_OUTPUT_SCHEMA,
				),
			],
		];

		$mock_config_store = Mockery::namedMock( ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_configuration' )
			->once()
			->with( self::MOCK_BLOCK_NAME )
			->andReturn( $mock_block_config );

		$query_results = BlockBindings::execute_query( $block_context, self::MOCK_OPERATION_NAME );
		$this->assertSame( $query_results, self::MOCK_OUTPUT_QUERY_RESULTS );

		/**
		 * Assert that the query runner received the correct input after transformations were applied.
		 */
		$this->assertSame( $mock_qr->getLastExecuteCallInput(), [
			'test_input_field' => 'test_value another_value',
			'another_input_field' => 'another_value',
		] );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_execute_query_with_query_input_transformations_and_overrides(): void {
		/**
		 * Set the query var to an override value.
		 */
		MockWordPressFunctions::set_query_var( 'test_input_field', 'override_value' );

		/**
		 * Mock the QueryRunner to return a result.
		 */
		$mock_qr = new MockQueryRunner();
		$mock_qr->addResult( 'output_field', 'Test Output Value' );

		$block_context = [
			'blockName' => self::MOCK_BLOCK_NAME,
			'queryInput' => [
				'test_input_field' => 'test_value',
			],
			'queryInputOverrides' => [
				'test_input_field' => [
					'type' => 'url',
					'display' => '/test_input_field/{test_input_field}',
				],
			],
		];

		$input_schema = [
			'test_input_field' => [
				'name' => 'Test Input Field',
				'type' => 'string',
				'generate' => function ( array $data ): string {
					return $data['test_input_field'] . ' transformed';
				},
			],
		];

		$mock_block_config = [
			'queries' => [
				'__DISPLAY__' => new MockQueryContext(
					$mock_qr,
					$input_schema,
					self::MOCK_OUTPUT_SCHEMA,
				),
			],
		];

		$mock_config_store = Mockery::namedMock( ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_configuration' )
			->once()
			->with( self::MOCK_BLOCK_NAME )
			->andReturn( $mock_block_config );

		$query_results = BlockBindings::execute_query( $block_context, self::MOCK_OPERATION_NAME );
		$this->assertSame( $query_results, self::MOCK_OUTPUT_QUERY_RESULTS );

		/**
		 * Assert that the query runner received the correct input after transformations and overrides were applied.
		 */
		$this->assertSame( $mock_qr->getLastExecuteCallInput(), [
			'test_input_field' => 'override_value transformed',
		] );
	}
}
