<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Editor\DataBinding;

use PHPUnit\Framework\TestCase;
use Mockery;
use RemoteDataBlocks\Config\Query\HttpQueryInterface;
use RemoteDataBlocks\Editor\BlockManagement\ConfigRegistry;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\Editor\DataBinding\BlockBindings;
use RemoteDataBlocks\Tests\Mocks\MockQueryRunner;
use RemoteDataBlocks\Tests\Mocks\MockQuery;
use RemoteDataBlocks\Tests\Mocks\MockWordPressFunctions;

class BlockBindingsTest extends TestCase {
	private const MOCK_BLOCK_NAME = 'test/block';

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

	protected function setUp(): void {
		parent::setUp();
		MockWordPressFunctions::reset();
	}

	protected function tearDown(): void {
		parent::tearDown();
		Mockery::close();
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_value_with_no_config(): void {
		/**
		 * Mock the ConfigStore to return null.
		 */
		$mock_config_store = Mockery::namedMock( ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_block_configuration' )
			->once()
			->with( self::MOCK_BLOCK_NAME )
			->andReturn( null );

		$block = [
			'context' => [
				BlockBindings::$context_name => [
					'blockName' => self::MOCK_BLOCK_NAME,
				],
			],
		];

		$value = BlockBindings::get_value( [ 'field' => 'test' ], $block, 'content' );

		// Assert that the value is null as no configuration was found.
		$this->assertNull( $value );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_value_with_overrides(): void {
		/**
		 * Mock the QueryRunner to return a result.
		 */
		$mock_qr = new MockQueryRunner();
		$mock_qr->addResult( self::MOCK_OUTPUT_FIELD_NAME, self::MOCK_OUTPUT_FIELD_VALUE );

		$block = [
			'context' => [
				BlockBindings::$context_name => [
					'blockName' => self::MOCK_BLOCK_NAME,
					'enabledOverrides' => [ 'test_input_field_override' ],
					'queryInput' => [
						'test_input_field' => 'test_value',
					],
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
				ConfigRegistry::DISPLAY_QUERY_KEY => MockQuery::create( [
					'input_schema' => $input_schema,
					'output_schema' => self::MOCK_OUTPUT_SCHEMA,
					'query_runner' => $mock_qr,
				] ),
			],
		];

		MockWordPressFunctions::add_mock_filter( 'remote_data_blocks_query_input_variables', [ 'test_input_field' => 'override_value' ] );

		$mock_config_store = Mockery::namedMock( ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_block_configuration' )
			->once()
			->with( self::MOCK_BLOCK_NAME )
			->andReturn( $mock_block_config );

		$value = BlockBindings::get_value( [ 'field' => self::MOCK_OUTPUT_FIELD_NAME ], $block, 'content' );

		// Assert that the value is correct.
		$this->assertSame( 'Test Output Value', $value );

		// Assert that the override was applied.
		$filter_args = MockWordPressFunctions::get_done_filter( 'remote_data_blocks_query_input_variables' );
		$this->assertSame( 'test_input_field_override', $filter_args[0][0] ?? null );

		/**
		 * Assert that the query runner received the correct input after overrides were applied.
		 */
		$this->assertSame( $mock_qr->getLastExecuteCallInput(), [
			'test_input_field' => 'override_value',
		] );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_value_with_query_input_transformed_by_custom_query_runner(): void {
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

		$block = [
			'context' => [
				BlockBindings::$context_name => [
					'blockName' => self::MOCK_BLOCK_NAME,
					'queryInput' => [
						'test_input_field' => 'test_value',
						'another_input_field' => 'another_value',
					],
				],
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
				ConfigRegistry::DISPLAY_QUERY_KEY => MockQuery::create( [
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

		$value = BlockBindings::get_value( [ 'field' => self::MOCK_OUTPUT_FIELD_NAME ], $block, 'content' );

		// Assert that the value is correct.
		$this->assertSame( $value, 'Test Output Value' );

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
	 * @preserveGlobalState disabled
	 */
	public function test_get_value_with_query_input_transformations_and_overrides(): void {
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

		$block = [
			'context' => [
				BlockBindings::$context_name => [
					'blockName' => self::MOCK_BLOCK_NAME,
					'queryInput' => [
						'test_input_field' => 'test_value',
					],
					'enabledOverrides' => [ 'test_input_field_override' ],
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
				ConfigRegistry::DISPLAY_QUERY_KEY => MockQuery::create( [
					'input_schema' => $input_schema,
					'output_schema' => self::MOCK_OUTPUT_SCHEMA,
					'query_runner' => $mock_qr,
				] ),
			],
		];

		MockWordPressFunctions::add_mock_filter( 'remote_data_blocks_query_input_variables', [ 'test_input_field' => 'override_value' ] );

		$mock_config_store = Mockery::namedMock( ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_block_configuration' )
			->once()
			->with( self::MOCK_BLOCK_NAME )
			->andReturn( $mock_block_config );

		$value = BlockBindings::get_value( [ 'field' => self::MOCK_OUTPUT_FIELD_NAME ], $block, 'content' );
		$this->assertSame( 'Test Output Value', $value );

		// Assert that the override was applied.
		$filter_args = MockWordPressFunctions::get_done_filter( 'remote_data_blocks_query_input_variables' );
		$this->assertSame( 'test_input_field_override', $filter_args[0][0] ?? null );

		/**
		 * Assert that the query runner received the correct input after transformations and overrides were applied.
		 */
		$this->assertSame( $mock_qr->getLastExecuteCallInput(), [
			'test_input_field' => 'override_value transformed',
		] );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_value(): void {
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
			],
		];

		$mock_block_config = [
			'queries' => [
				ConfigRegistry::DISPLAY_QUERY_KEY => MockQuery::create( [
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

		$source_args = [
			'field' => self::MOCK_OUTPUT_FIELD_NAME,
		];

		$block = [
			'context' => [
				BlockBindings::$context_name => $block_context,
			],
		];

		$remote_value = BlockBindings::get_value( $source_args, $block, 'content' );
		$this->assertSame( $remote_value, self::MOCK_OUTPUT_FIELD_VALUE );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_value_with_non_string(): void {
		/**
		 * Mock the QueryRunner to return a result.
		 */
		$mock_qr = new MockQueryRunner();
		$mock_qr->addResult( 'output_field', 123 );

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
			],
		];

		$mock_block_config = [
			'queries' => [
				ConfigRegistry::DISPLAY_QUERY_KEY => MockQuery::create( [
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

		$source_args = [
			'field' => self::MOCK_OUTPUT_FIELD_NAME,
		];

		$block = [
			'context' => [
				BlockBindings::$context_name => $block_context,
			],
		];

		$remote_value = BlockBindings::get_value( $source_args, $block, 'content' );
		$this->assertSame( $remote_value, '123' );
	}

	public function test_get_value_with_fallback_content_attribute(): void {
		$block = [
			'attributes' => [
				'content' => 'Fallback Content',
			],
		];

		$remote_value = BlockBindings::get_value( [ 'field' => 'non_existent' ], $block, 'content' );
		$this->assertSame( $remote_value, 'Fallback Content' );
	}

	public function test_get_value_with_non_string_fallback_content_attribute(): void {
		$block = [
			'attributes' => [
				'content' => 123,
			],
		];

		$remote_value = BlockBindings::get_value( [ 'field' => 'non_existent' ], $block, 'content' );
		$this->assertSame( $remote_value, '123' );
	}

	public function test_get_value_with_null_fallback_content_attribute(): void {
		$block = [
			'attributes' => [
				'content' => null,
			],
		];

		$remote_value = BlockBindings::get_value( [ 'field' => 'non_existent' ], $block, 'content' );
		$this->assertNull( $remote_value );
	}

	public function test_get_value_with_fallback_url_attribute(): void {
		$block = [
			'attributes' => [
				'content' => 'Fallback Content',
				'url' => 'https://example.com/hello-world',
			],
		];

		$remote_value = BlockBindings::get_value( [ 'field' => 'non_existent' ], $block, 'url' );
		$this->assertSame( $remote_value, 'https://example.com/hello-world' );
	}

	public function test_get_value_with_non_string_fallback_url_attribute(): void {
		$block = [
			'attributes' => [
				'content' => 'Fallback Content',
				'url' => 123,
			],
		];

		$remote_value = BlockBindings::get_value( [ 'field' => 'non_existent' ], $block, 'url' );
		$this->assertSame( $remote_value, '123' );
	}

	public function test_get_value_with_null_fallback_url_attribute(): void {
		$block = [
			'attributes' => [
				'content' => 'Fallback Content',
				'url' => null,
			],
		];

		$remote_value = BlockBindings::get_value( [ 'field' => 'non_existent' ], $block, 'url' );
		$this->assertNull( $remote_value );
	}

	public function test_get_value_with_fallback_results_context(): void {
		$block = [
			'context' => [
				BlockBindings::$context_name => [
					'blockName' => self::MOCK_BLOCK_NAME,
					'queryInput' => [],
					'results' => [
						[
							'result' => [
								self::MOCK_OUTPUT_FIELD_NAME => 'Stored Output Value',
							],
						],
					],
				],
			],
		];

		$remote_value = BlockBindings::get_value( [ 'field' => self::MOCK_OUTPUT_FIELD_NAME ], $block, 'content' );
		$this->assertSame( $remote_value, 'Stored Output Value' );
	}

	public function test_get_value_with_non_string_fallback_results_context(): void {
		$block = [
			'context' => [
				BlockBindings::$context_name => [
					'blockName' => self::MOCK_BLOCK_NAME,
					'queryInput' => [],
					'results' => [
						[
							'result' => [
								self::MOCK_OUTPUT_FIELD_NAME => 456,
							],
						],
					],
				],
			],
		];

		$remote_value = BlockBindings::get_value( [ 'field' => self::MOCK_OUTPUT_FIELD_NAME ], $block, 'content' );
		$this->assertSame( $remote_value, '456' );
	}

	public function test_get_value_with_null_fallback_results_context(): void {
		$block = [
			'context' => [
				BlockBindings::$context_name => [
					'blockName' => self::MOCK_BLOCK_NAME,
					'queryInput' => [],
					'results' => [
						[
							'result' => [
								self::MOCK_OUTPUT_FIELD_NAME => null,
							],
						],
					],
				],
			],
		];

		$remote_value = BlockBindings::get_value( [ 'field' => self::MOCK_OUTPUT_FIELD_NAME ], $block, 'content' );
		$this->assertNull( $remote_value );
	}
}
