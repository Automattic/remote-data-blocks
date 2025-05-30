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
use WP_Error;

class BlockBindingsTest extends TestCase {
	private const MOCK_BLOCK_NAME = 'test/block';

	private const MOCK_INPUT_SCHEMA = [
		'test_input_field' => [
			'name' => 'Test Input Field',
			'type' => 'string',
		],
		'another_input_field' => [
			'name' => 'Another Input Field',
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
	public function test_should_render_fallback_content_with_unknown_mode_with_error(): void {
		$mock_qr = $this->create_mock_query_runner_with_error( new WP_Error( 'test-error', 'Test Error' ) );
		$mock_block_config = $this->create_mock_block_config( $mock_qr );
		$this->create_mock_config_store( $mock_block_config );

		$this->assertFalse( BlockBindings::should_render_fallback_content(
			$this->create_block_context( [
				'test_input_field' => 'test_value',
				'another_input_field' => 'another_value',
			] ),
			[ 'mode' => 'unknown' ]
		) );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_should_render_fallback_content_with_error_mode(): void {
		$mock_qr = $this->create_mock_query_runner_with_error( new WP_Error( 'test-error', 'Test Error' ) );
		$mock_block_config = $this->create_mock_block_config( $mock_qr );
		$this->create_mock_config_store( $mock_block_config );

		$this->assertTrue( BlockBindings::should_render_fallback_content(
			$this->create_block_context( [
				'test_input_field' => 'test_value',
				'another_input_field' => 'another_value',
			] ),
			[ 'mode' => 'error' ]
		) );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_should_render_fallback_content_with_results_for_error_mode(): void {
		$mock_qr = $this->create_mock_query_runner_with_result( [ 'result' => 'test_result' ] );
		$mock_block_config = $this->create_mock_block_config( $mock_qr );
		$this->create_mock_config_store( $mock_block_config );

		$this->assertFalse( BlockBindings::should_render_fallback_content(
			$this->create_block_context( [
				'test_input_field' => 'test_value',
				'another_input_field' => 'another_value',
			] ),
			[ 'mode' => 'error' ]
		) );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_should_render_fallback_content_with_no_results_for_error_mode(): void {
		$mock_qr = new MockQueryRunner();
		$mock_block_config = $this->create_mock_block_config( $mock_qr );
		$this->create_mock_config_store( $mock_block_config );

		$this->assertTrue( BlockBindings::should_render_fallback_content(
			$this->create_block_context( [
				'test_input_field' => 'test_value',
				'another_input_field' => 'another_value',
			] ),
			[ 'mode' => 'error' ]
		) );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_should_render_fallback_content_with_results_for_empty_mode(): void {
		$mock_qr = $this->create_mock_query_runner_with_result( [ 'result' => 'test_result' ] );
		$mock_block_config = $this->create_mock_block_config( $mock_qr );
		$this->create_mock_config_store( $mock_block_config );

		$this->assertFalse( BlockBindings::should_render_fallback_content(
			$this->create_block_context( [
				'test_input_field' => 'test_value',
				'another_input_field' => 'another_value',
			] ),
			[ 'mode' => 'empty' ]
		) );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_should_render_fallback_content_with_error_for_empty_mode(): void {
		$mock_qr = $this->create_mock_query_runner_with_error( new WP_Error( 'test-error', 'Test Error' ) );
		$mock_block_config = $this->create_mock_block_config( $mock_qr );
		$this->create_mock_config_store( $mock_block_config );

		$this->assertFalse( BlockBindings::should_render_fallback_content(
			$this->create_block_context( [
				'test_input_field' => 'test_value',
				'another_input_field' => 'another_value',
			] ),
			[ 'mode' => 'empty' ]
		) );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_should_render_fallback_content_with_empty_mode(): void {
		$mock_qr = new MockQueryRunner();
		$mock_qr->setResults( [] );
		$mock_block_config = $this->create_mock_block_config( $mock_qr );
		$this->create_mock_config_store( $mock_block_config );

		$this->assertTrue( BlockBindings::should_render_fallback_content(
			$this->create_block_context( [
				'test_input_field' => 'test_value',
				'another_input_field' => 'another_value',
			] ),
			[ 'mode' => 'empty' ]
		) );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_should_render_fallback_content_with_unknown_mode_with_empty_results(): void {
		$mock_qr = new MockQueryRunner();
		$mock_block_config = $this->create_mock_block_config( $mock_qr );
		$this->create_mock_config_store( $mock_block_config );

		$this->assertFalse( BlockBindings::should_render_fallback_content(
			$this->create_block_context( [
				'test_input_field' => 'test_value',
				'another_input_field' => 'another_value',
			] ),
			[ 'mode' => 'unknown' ]
		) );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_value_with_no_config(): void {
		$this->create_mock_config_store( null );

		$block = [
			'context' => $this->create_block_context( [] ),
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
		$mock_qr = $this->create_mock_query_runner_with_result( self::MOCK_OUTPUT_FIELD_VALUE );
		$mock_block_config = $this->create_mock_block_config( $mock_qr );
		$this->create_mock_config_store( $mock_block_config );

		$block = [
			'context' => $this->create_block_context(
				[ 'test_input_field' => 'test_value' ],
				[ 'test_input_field_override' ]
			),
		];

		MockWordPressFunctions::add_mock_filter( 'remote_data_blocks_query_input_variables', [ 'test_input_field' => 'override_value' ] );

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
		$mock_qr->setResults( [
			[
				self::MOCK_OUTPUT_FIELD_NAME => [ 'value' => 'Test Output Value' ],
			],
		] );

		$block = [
			'context' => $this->create_block_context( [
				'test_input_field' => 'test_value',
				'another_input_field' => 'another_value',
			] ),
		];

		$mock_block_config = $this->create_mock_block_config( $mock_qr );

		$this->create_mock_config_store( $mock_block_config );

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
		$mock_qr = new class() extends MockQueryRunner {
			public function execute( HttpQueryInterface $query, array $input_variables ): array {
				$input_variables['test_input_field'] .= ' transformed';
				return parent::execute( $query, $input_variables );
			}
		};
		$mock_qr->setResults( [
			[
				self::MOCK_OUTPUT_FIELD_NAME => [ 'value' => 'Test Output Value' ],
			],
		] );

		$block = [
			'context' => $this->create_block_context( [
				'test_input_field' => 'test_value',
			], [ 'test_input_field_override' ] ),
		];

		$mock_block_config = $this->create_mock_block_config( $mock_qr );

		MockWordPressFunctions::add_mock_filter( 'remote_data_blocks_query_input_variables', [ 'test_input_field' => 'override_value' ] );

		$this->create_mock_config_store( $mock_block_config );

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
		$mock_qr = $this->create_mock_query_runner_with_result( self::MOCK_OUTPUT_FIELD_VALUE );
		$mock_block_config = $this->create_mock_block_config( $mock_qr );
		$this->create_mock_config_store( $mock_block_config );

		$block = [
			'context' => $this->create_block_context( [
				'test_input_field' => 'test_value',
			] ),
		];

		$remote_value = BlockBindings::get_value( [ 'field' => self::MOCK_OUTPUT_FIELD_NAME ], $block, 'content' );
		$this->assertSame( $remote_value, self::MOCK_OUTPUT_FIELD_VALUE );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_value_with_non_string(): void {
		$mock_qr = $this->create_mock_query_runner_with_result( 123 );
		$mock_block_config = $this->create_mock_block_config( $mock_qr );
		$this->create_mock_config_store( $mock_block_config );

		$block = [
			'context' => $this->create_block_context( [
				'test_input_field' => 'test_value',
			] ),
		];

		$remote_value = BlockBindings::get_value( [ 'field' => self::MOCK_OUTPUT_FIELD_NAME ], $block, 'content' );
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

	/**
	 * Creates a mock query runner that will return a result once.
	 *
	 * @param mixed $value The result value to return from the query runner.
	 */
	private function create_mock_query_runner_with_result( mixed $value = null ): MockQueryRunner {
		$mock_qr = new MockQueryRunner();
		$mock_qr->setResults( [
			[
				self::MOCK_OUTPUT_FIELD_NAME => [ 'value' => $value ],
			],
		] );
		return $mock_qr;
	}

	/**
	 * Creates a mock query runner that will return an error once.
	 *
	 * @param WP_Error $error The error to return from the query runner.
	 */
	private function create_mock_query_runner_with_error( WP_Error $error ): MockQueryRunner {
		$mock_qr = new MockQueryRunner();
		$mock_qr->setResults( $error );
		return $mock_qr;
	}

	/**
	 * Creates a mock block configuration.
	 *
	 * @param MockQueryRunner $query_runner The query runner to use.
	 */
	private function create_mock_block_config( MockQueryRunner $query_runner ): array {
		return [
			'queries' => [
				ConfigRegistry::DISPLAY_QUERY_KEY => MockQuery::create( [
					'input_schema' => self::MOCK_INPUT_SCHEMA,
					'output_schema' => self::MOCK_OUTPUT_SCHEMA,
					'query_runner' => $query_runner,
				] ),
			],
		];
	}

	/**
	 * Creates a mock config store.
	 *
	 * @param array|null $block_config The block configuration to return.
	 */
	private function create_mock_config_store( ?array $block_config ): \Mockery\MockInterface {
		$mock_config_store = Mockery::namedMock( ConfigStore::class );
		$mock_config_store->shouldReceive( 'get_block_configuration' )
			->once()
			->with( self::MOCK_BLOCK_NAME )
			->andReturn( $block_config );
		return $mock_config_store;
	}

	/**
	 * Creates a block context array.
	 *
	 * @param array $query_input The query input to use.
	 * @param array $enabled_overrides Optional enabled overrides.
	 */
	private function create_block_context( array $query_input, array $enabled_overrides = [] ): array {
		return [
			BlockBindings::$context_name => [
				'blockName' => self::MOCK_BLOCK_NAME,
				'queryInput' => $query_input,
				'enabledOverrides' => $enabled_overrides,
			],
		];
	}
}
