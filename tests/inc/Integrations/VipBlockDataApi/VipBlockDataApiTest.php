<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Integrations\VipBlockDataApi;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Editor\BlockManagement\ConfigRegistry;
use RemoteDataBlocks\Editor\DataBinding\BlockBindings;
use RemoteDataBlocks\Integrations\VipBlockDataApi\VipBlockDataApi;
use RemoteDataBlocks\Tests\Mocks\MockLogger;
use RemoteDataBlocks\Tests\Mocks\MockQuery;
use RemoteDataBlocks\Tests\Mocks\MockQueryRunner;
use RemoteDataBlocks\Tests\Mocks\MockWordPressFunctions;

use function register_remote_data_block;

class VipBlockDataApiTest extends TestCase {
	private static $sourced_block1 = [
		'name' => 'remote-data-blocks/events',
		'attributes' => [
			'remoteData' => [
				'blockName' => 'remote-data-blocks/events',
				'queryInput' => [
					'record_id' => 'rec9H65WdbaeuxxaU',
				],
			],
		],
		'innerBlocks' => [
			[
				'name' => 'core/paragraph',
				'attributes' => [
					'metadata' => [
						'bindings' => [
							'content' => [
								'source' => 'remote-data/binding',
								'args' => [
									'field' => 'title',
								],
							],
						],
					],
					'content' => 'Happy hour &amp; networking',
					'dropCap' => false,
				],
			],
			[
				'name' => 'core/heading',
				'attributes' => [
					'metadata' => [
						'bindings' => [
							'content' => [
								'source' => 'remote-data/binding',
								'args' => [
									'field' => 'location',
								],
							],
						],
					],
					'content' => "President's dining hall",
					'level' => 2,
				],
			],
		],
	];

	private static $parsed_block1 = [
		'blockName' => 'remote-data-blocks/events',
		'attrs' => [
			'remoteData' => [
				'blockName' => 'remote-data-blocks/events',
				'queryInput' => [
					'record_id' => 'rec9H65WdbaeuxxaU',
				],
			],
		],
		'innerBlocks' => [
			[
				'blockName' => 'core/paragraph',
				'attrs' => [
					'metadata' => [
						'bindings' => [
							'content' => [
								'source' => 'remote-data/binding',
								'args' => [
									'field' => 'title',
								],
							],
						],
					],
				],
				'innerBlocks' => [],
				'innerHTML' => "\n<p>Happy hour &amp; networking</p>\n",
				'innerContent' => [
					"\n<p>Happy hour &amp; networking</p>\n",
				],
			],
			[
				'blockName' => 'core/heading',
				'attrs' => [
					'metadata' => [
						'bindings' => [
							'content' => [
								'source' => 'remote-data/binding',
								'args' => [
									'field' => 'location',
								],
							],
						],
					],
				],
				'innerBlocks' => [],
				'innerHTML' => "\n<h2 class=\"wp-block-heading\">President's dining hall</h2>\n",
				'innerContent' => [
					"\n<h2 class=\"wp-block-heading\">President's dining v</h2>\n",
				],
			],
		],
		'innerHTML' => "\n<div class=\"wp-block-remote-data-blocks-events\">\n\n</div>\n",
		'innerContent' => [
			"\n<div class=\"wp-block-remote-data-blocks-events\">",
			null,
			"\n\n",
			null,
			"</div>\n",
		],
	];

	protected function setUp(): void {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		\set_error_handler(
			static function ( $errno, $errstr ): void {
				throw new \Exception( $errstr, $errno ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			},
			E_USER_WARNING
		);

		MockWordPressFunctions::reset();
		ConfigRegistry::init();
	}

	protected function tearDown(): void {
		\restore_error_handler();
	}

	public function testResolveRemoteDataSimple(): void {
		$expected1 = 'Happy happy hour! No networking!';
		$expected2 = 'Comedor del Presidente';

		$mock_qr = new MockQueryRunner();
		$mock_qr->setResults( [
			[
				'title' => [ 'value' => $expected1 ],
				'location' => [ 'value' => $expected2 ],
			],
		] );

		$mock_query = MockQuery::create( [ 'query_runner' => $mock_qr ] );
		register_remote_data_block( [
			'title' => 'Events',
			'render_query' => [
				'query' => $mock_query,
			],
		] );

		$result = VipBlockDataApi::resolve_remote_data( self::$sourced_block1, 'remote-data-blocks/events', 12, self::$parsed_block1 );
		$this->assertSame( $expected1, $result['innerBlocks'][0]['attributes']['content'] );
		$this->assertSame( $expected2, $result['innerBlocks'][1]['attributes']['content'] );
	}

	public function testResolveRemoteDataPassesThroughUnregisteredBlocks(): void {
		$result = VipBlockDataApi::resolve_remote_data( self::$sourced_block1, 'remote-data-blocks/events', 12, self::$parsed_block1 );
		$this->assertSame( 'Happy hour &amp; networking', $result['innerBlocks'][0]['attributes']['content'] );
		$this->assertSame( "President's dining hall", $result['innerBlocks'][1]['attributes']['content'] );
	}

	public function testResolveRemoteDataPreservesOtherSourcesInNestedBlocks(): void {
		$mock_qr = new MockQueryRunner();
		$mock_qr->setResults( [
			[ 'image' => [ 'value' => 'https://example.com/remote.jpg' ] ],
		] );
		register_remote_data_block( [
			'title' => 'Events',
			'render_query' => [
				'query' => MockQuery::create( [ 'query_runner' => $mock_qr ] ),
			],
		] );
		$mock_logger = new MockLogger();
		BlockBindings::init( $mock_logger );

		$image_attributes = [
			'metadata' => [
				'name' => 'Event photo',
				'bindings' => [
					'__default' => [ 'source' => 'core/pattern-overrides' ],
					'url' => [
						'source' => BlockBindings::$binding_source,
						'args' => [ 'field' => 'image' ],
					],
					'alt' => [
						'source' => 'example/other-source',
						'args' => [ 'field' => 'image' ],
					],
				],
			],
			'url' => 'https://example.com/saved.jpg',
			'alt' => 'Saved alternate text',
			'caption' => 'Saved pattern caption',
		];
		$paragraph_attributes = [
			'metadata' => [
				'bindings' => [
					'content' => [ 'source' => BlockBindings::$binding_source ],
				],
			],
			'content' => 'Saved content',
		];
		$sourced_block = self::$sourced_block1;
		$sourced_block['innerBlocks'] = [
			[
				'name' => 'core/group',
				'attributes' => [],
				'innerBlocks' => [
					[
						'name' => 'core/image',
						'attributes' => (object) $image_attributes,
					],
					[
						'name' => 'core/paragraph',
						'attributes' => $paragraph_attributes,
					],
				],
			],
		];

		$result = VipBlockDataApi::resolve_remote_data( $sourced_block, $sourced_block['name'], 12, self::$parsed_block1 );

		$image_attributes['url'] = 'https://example.com/remote.jpg';
		$this->assertSame( $image_attributes, $result['innerBlocks'][0]['innerBlocks'][0]['attributes'] );
		$this->assertSame( $paragraph_attributes, $result['innerBlocks'][0]['innerBlocks'][1]['attributes'] );
		$this->assertSame(
			[ 'Successfully resolved block binding', 'Missing field mapping for block binding' ],
			array_column( $mock_logger->getLogs(), 'message' )
		);
	}

	public function testResolveRemoteDataFallsBackToSerializedDataOnError(): void {
		$error = new \WP_Error( 'rdb-uh-oh', 'uh-oh!' );
		$mock_qr = new MockQueryRunner();
		$mock_qr->setResults( $error );
		$mock_query = MockQuery::create( [ 'query_runner' => $mock_qr ] );

		register_remote_data_block( [
			'title' => 'Events',
			'render_query' => [
				'query' => $mock_query,
			],
		] );

		$mock_logger = new MockLogger();

		BlockBindings::init( $mock_logger );

		$result = VipBlockDataApi::resolve_remote_data( self::$sourced_block1, 'remote-data-blocks/events', 12, self::$parsed_block1 );
		$logs = $mock_logger->getLogs();

		// The binding callback results in an error.
		$this->assertSame(
			[
				'level' => 'error',
				'message' => 'uh-oh!',
				'context' => [
					'block_name' => 'core/paragraph',
					'block_info' => [
						'source_args' => [
							'field' => 'title',
						],
					],
					'remote_data_block_name' => 'remote-data-blocks/events',
					'error' => $error,
					'type' => 'block-binding',
				],
			],
			$logs[0]
		);
		$this->assertSame( 'Happy hour &amp;amp; networking', $result['innerBlocks'][0]['attributes']['content'] );
		$this->assertSame( 'President&#039;s dining hall', $result['innerBlocks'][1]['attributes']['content'] );
	}
}
