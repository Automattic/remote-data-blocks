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
use RemoteDataBlocks\Config\Query\HttpQueryInterface;
use RemoteDataBlocks\Editor\BlockManagement\ConfigRegistry;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\Editor\DataBinding\BlockBindings;
use RemoteDataBlocks\Tests\Mocks\MockQueryRunner;
use RemoteDataBlocks\Tests\Mocks\MockWordPressFunctions;
use RemoteDataBlocks\Tests\Mocks\MockQuery;

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
		'type' => [
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
		$mock_config_store->shouldReceive( 'get_block_configuration' )
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
				ConfigRegistry::DISPLAY_QUERY_KEY => MockQuery::from_array( [
					'input_schema' => self::MOCK_INPUT_SCHEMA,
					'output_schema' => self::MOCK_OUTPUT_SCHEMA,
					'query_runner' => $mock_qr,
				] ),
			],
		];

		/**
		 * Mock the ConfigStore to return the block configuration.
		 */
		$mock_config_store = Mockery::namedMock( ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_block_configuration' )
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
		MockWordPressFunctions::set_query_var( 'test_query_var', 'override_value' );

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
					'source' => 'test_query_var',
					'sourceType' => 'query_var',
				],
			],
		];

		$input_schema = [
			'test_input_field' => [
				'name' => 'Test Input Field',
				'type' => 'string',
			],
		];

		$mock_block_config = [
			'queries' => [
				ConfigRegistry::DISPLAY_QUERY_KEY => MockQuery::from_array( [
					'input_schema' => $input_schema,
					'output_schema' => self::MOCK_OUTPUT_SCHEMA,
					'query_runner' => $mock_qr,
				] ),
				'query_input_overrides' => [
					[
						'query' => ConfigRegistry::DISPLAY_QUERY_KEY,
						'source' => 'test_query_var',
						'source_type' => 'query_var',
						'target' => 'test_input_field',
						'target_type' => 'input_var',
					],
				],
			],
		];

		$mock_config_store = Mockery::namedMock( ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_block_configuration' )
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
	public function test_execute_query_with_query_input_transformed_by_custom_query_runner(): void {
		/**
		 * Mock the QueryRunner to return a result.
		 */
		$mock_qr = new class() extends MockQueryRunner {
			public function execute( HttpQueryInterface $query, array $input_variables ): array {
				$input_variables['test_input_field'] .= ' ' . $input_variables['another_input_field'];
				return parent::execute( $query, $input_variables );
			}
		};
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
			],
			'another_input_field' => [
				'name' => 'Another Input Field',
				'type' => 'string',
			],
		];

		$mock_block_config = [
			'queries' => [
				ConfigRegistry::DISPLAY_QUERY_KEY => MockQuery::from_array( [
					'input_schema' => $input_schema,
					'output_schema' => self::MOCK_OUTPUT_SCHEMA,
					'query_runner' => $mock_qr,
				] ),
			],
		];

		$mock_config_store = Mockery::namedMock( ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_block_configuration' )
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
		MockWordPressFunctions::set_query_var( 'test_query_var', 'override_value' );

		/**
		 * Mock the QueryRunner to return a result.
		 */
		/**
		 * Mock the QueryRunner to return a result.
		 */
		$mock_qr = new class() extends MockQueryRunner {
			public function execute( HttpQueryInterface $query, array $input_variables ): array {
				$input_variables['test_input_field'] .= ' transformed';
				return parent::execute( $query, $input_variables );
			}
		};
		$mock_qr->addResult( 'output_field', 'Test Output Value' );

		$block_context = [
			'blockName' => self::MOCK_BLOCK_NAME,
			'queryInput' => [
				'test_input_field' => 'test_value',
			],
			'queryInputOverrides' => [
				'test_input_field' => [
					'source' => 'test_query_var',
					'sourceType' => 'query_var',
				],
			],
		];

		$input_schema = [
			'test_input_field' => [
				'name' => 'Test Input Field',
				'type' => 'string',
			],
		];

		$mock_block_config = [
			'queries' => [
				ConfigRegistry::DISPLAY_QUERY_KEY => MockQuery::from_array( [
					'input_schema' => $input_schema,
					'output_schema' => self::MOCK_OUTPUT_SCHEMA,
					'query_runner' => $mock_qr,
				] ),
			],
		];

		$mock_config_store = Mockery::namedMock( ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_block_configuration' )
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
