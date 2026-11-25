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
			public function execute( HttpQueryInterface $query, array $input_variables ): array|\WP_Error {
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
			public function execute( HttpQueryInterface $query, array $input_variables ): array|\WP_Error {
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
	 * @dataProvider scalar_target_provider
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_value_respects_registered_scalar_target( mixed $target_type, mixed $remote_value, mixed $expected ): void {
		$this->create_mock_config_store( $this->create_mock_block_config( $this->create_mock_query_runner_with_result( $remote_value ) ) );
		$block = $this->create_mock_bound_block( $target_type );
		$block->context = $this->create_block_context( [] );

		$this->assertSame( $expected, BlockBindings::get_value( [
			'field' => self::MOCK_OUTPUT_FIELD_NAME,
			'label' => 'Do not prefix typed values',
		], $block, 'value' ) );
	}

	public function scalar_target_provider(): array {
		return [
			'number zero' => [ 'number', 0, 0 ],
			'number decimal' => [ 'number', 1.25, 1.25 ],
			'number numeric string' => [ 'number', ' 1.25e2 ', 125.0 ],
			'number decimal point' => [ 'number', '.5', 0.5 ],
			'number signed string' => [ 'number', '+12', 12.0 ],
			'number rejects partial value' => [ 'number', '12px', null ],
			'number rejects currency' => [ 'number', '$12.00', null ],
			'number rejects empty string' => [ 'number', '', null ],
			'number rejects whitespace' => [ 'number', '  ', null ],
			// phpcs:ignore PHPCompatibility.Miscellaneous.ValidIntegers.HexNumericStringFound -- Invalid binding input, not numeric PHP syntax.
			'number rejects hex' => [ 'number', '0x12', null ],
			'number rejects boolean' => [ 'number', false, null ],
			'number rejects overflow' => [ 'number', '1e999', null ],
			'number rejects nonfinite' => [ 'number', INF, null ],
			'number rejects NaN' => [ 'number', NAN, null ],
			'integer zero' => [ 'integer', 0, 0 ],
			'integer accepts integral string' => [ 'integer', '42.0', 42 ],
			'integer accepts exponent' => [ 'integer', '4.2e1', 42 ],
			'integer accepts integral float' => [ 'integer', 42.0, 42 ],
			'integer rejects fraction' => [ 'integer', 4.2, null ],
			'integer rejects fractional string' => [ 'integer', '4.2', null ],
			'integer accepts safe limit' => [ 'integer', '9007199254740991', 9007199254740991 ],
			'integer rejects unsafe value' => [ 'integer', '9007199254740992', null ],
			'integer rejects negative unsafe value' => [ 'integer', '-9007199254740992', null ],
			'boolean true' => [ 'boolean', true, true ],
			'boolean false' => [ 'boolean', false, false ],
			'boolean rejects string' => [ 'boolean', 'false', null ],
			'boolean rejects number' => [ 'boolean', 0, null ],
			'missing number' => [ 'number', null, null ],
			'missing boolean' => [ 'boolean', null, null ],
			'string retains display zero' => [ 'string', 0, '0' ],
			'string retains currency formatting' => [ 'string', '$12.00', '$12.00' ],
			'rich text retains markup' => [ 'rich-text', '<em>Formatted</em>', '<em>Formatted</em>' ],
			'object target rejected' => [ 'object', 'text', null ],
			'array target rejected' => [ 'array', 'text', null ],
			'compound schema rejected' => [ [ 'number', 'null' ], 42, null ],
		];
	}

	/**
	 * @dataProvider scalar_fallback_provider
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_value_applies_target_type_to_saved_fallback( string $target_type, mixed $saved_value, mixed $expected ): void {
		$block = $this->create_mock_bound_block( $target_type );
		/** @psalm-suppress UndefinedPropertyAssignment -- WP_Block exposes read-only magic attributes; this lightweight unit mock supplies them directly. */
		$block->attributes = [ 'value' => $saved_value ];

		$this->assertSame( $expected, BlockBindings::get_value( [], $block, 'value' ) );
	}

	public function scalar_fallback_provider(): array {
		return [
			'number fallback zero' => [ 'number', 0, 0 ],
			'number fallback string' => [ 'number', '12.5', 12.5 ],
			'integer fallback' => [ 'integer', '42', 42 ],
			'false fallback' => [ 'boolean', false, false ],
			'true fallback' => [ 'boolean', true, true ],
			'null fallback' => [ 'number', null, null ],
			'invalid number fallback' => [ 'number', '12px', null ],
			'invalid boolean fallback' => [ 'boolean', 'false', null ],
			'string fallback stays sanitized' => [ 'string', '<em>Fallback</em>', 'Fallback' ],
		];
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_value_uses_typed_serialized_fallback_when_remote_value_is_invalid(): void {
		$this->create_mock_config_store( $this->create_mock_block_config( $this->create_mock_query_runner_with_result( 'invalid number' ) ) );
		$block = $this->create_mock_bound_block( 'integer' );
		$context = $this->create_block_context( [] );
		$context[ BlockBindings::$context_name ]['results'] = [
			[
				'uuid' => '10000000-1000-4000-8000-100000000000',
				'result' => [
					self::MOCK_OUTPUT_FIELD_NAME => [
						'name' => 'Stored number',
						'type' => 'integer',
						'value' => 0,
					],
				],
			],
		];
		$block->context = $context;
		/** @psalm-suppress UndefinedPropertyAssignment -- WP_Block exposes read-only magic attributes; this lightweight unit mock supplies them directly. */
		$block->attributes = [ 'value' => 99 ];

		$this->assertSame( 0, BlockBindings::get_value( [ 'field' => self::MOCK_OUTPUT_FIELD_NAME ], $block, 'value' ) );
	}

	/**
	 * @dataProvider serialized_scalar_fallback_provider
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_value_unwraps_current_serialized_field_descriptors( string $target_type, mixed $stored_value, mixed $expected ): void {
		$block = $this->create_mock_bound_block( $target_type );
		$block->context = [
			BlockBindings::$context_name => [
				'results' => [
					[
						'uuid' => '10000000-1000-4000-8000-100000000000',
						'result' => [
							self::MOCK_OUTPUT_FIELD_NAME => [
								'name' => 'Stored field',
								'type' => $target_type,
								'value' => $stored_value,
							],
						],
					],
				],
			],
		];

		$this->assertSame( $expected, BlockBindings::get_value( [ 'field' => self::MOCK_OUTPUT_FIELD_NAME ], $block, 'value' ) );
	}

	public function serialized_scalar_fallback_provider(): array {
		return [
			'false descriptor' => [ 'boolean', false, false ],
			'zero descriptor' => [ 'number', 0, 0 ],
			'integer descriptor' => [ 'integer', '42', 42 ],
			'null descriptor' => [ 'boolean', null, null ],
			'invalid descriptor' => [ 'number', '12px', null ],
			'string descriptor remains sanitized' => [ 'string', '<em>Fallback</em>', 'Fallback' ],
		];
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_value_preserves_string_labels_and_unknown_array_callers(): void {
		$this->create_mock_config_store( $this->create_mock_block_config( $this->create_mock_query_runner_with_result( 42 ) ) );
		$block = [
			'name' => '<inline-binding>',
			'context' => $this->create_block_context( [] ),
		];

		$this->assertSame( '<span class="rdb-block-label">Count</span> 42', BlockBindings::get_value( [
			'field' => self::MOCK_OUTPUT_FIELD_NAME,
			'label' => 'Count',
		], $block, 'content' ) );
	}

	/**
	 * @dataProvider structured_fallback_provider
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_value_ignores_structured_serialized_fallback( string $target_type, mixed $saved_attribute, mixed $expected ): void {
		$this->create_mock_config_store( $this->create_mock_block_config( $this->create_mock_query_runner_with_result( [
			'street' => '12 Rosewood Lane',
			'city' => 'Portland',
		] ) ) );
		$block = $this->create_mock_bound_block( $target_type );
		$context = $this->create_block_context( [] );
		$context[ BlockBindings::$context_name ]['results'] = [
			[
				'uuid' => '10000000-1000-4000-8000-100000000000',
				'result' => [
					self::MOCK_OUTPUT_FIELD_NAME => [
						'name' => 'Address',
						'type' => 'string',
						'value' => [
							'street' => '12 Rosewood Lane',
							'city' => 'Portland',
						],
					],
				],
			],
		];
		$block->context = $context;
		/** @psalm-suppress UndefinedPropertyAssignment -- WP_Block exposes read-only magic attributes; this lightweight unit mock supplies them directly. */
		$block->attributes = null === $saved_attribute ? [] : [ 'value' => $saved_attribute ];

		$this->assertSame( $expected, BlockBindings::get_value( [ 'field' => self::MOCK_OUTPUT_FIELD_NAME ], $block, 'value' ) );
	}

	public function structured_fallback_provider(): array {
		return [
			// null cancels the binding, so WordPress keeps the saved markup.
			'rich text without saved attribute cancels binding' => [ 'rich-text', null, null ],
			'string uses saved attribute' => [ 'string', 'Saved text', 'Saved text' ],
			'number uses saved attribute' => [ 'number', 7, 7 ],
		];
	}

	private function create_mock_bound_block( mixed $target_type ): \WP_Block {
		/** @var \WP_Block_Type $block_type */
		$block_type = Mockery::namedMock( \WP_Block_Type::class );
		$block_type->attributes = [ 'value' => [ 'type' => $target_type ] ];
		/** @var \WP_Block $block */
		$block = Mockery::namedMock( \WP_Block::class );
		$block->name = 'test/scalar-target';
		$block->context = [];
		/** @psalm-suppress UndefinedPropertyAssignment -- WP_Block exposes read-only magic attributes; this lightweight unit mock supplies them directly. */
		$block->attributes = [];
		$block->block_type = $block_type;
		return $block;
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
