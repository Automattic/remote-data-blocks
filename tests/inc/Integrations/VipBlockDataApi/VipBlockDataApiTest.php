<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Integrations\VipBlockDataApi;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Config\QueryRunner\QueryRunnerInterface;
use RemoteDataBlocks\Editor\BlockManagement\ConfigRegistry;
use RemoteDataBlocks\Integrations\VipBlockDataApi\VipBlockDataApi;
use RemoteDataBlocks\Tests\Mocks\MockQueryRunner;
use RemoteDataBlocks\Tests\Mocks\MockDataSource;
use RemoteDataBlocks\Tests\Mocks\MockValidator;

use function register_remote_data_block;

class TestQueryContext extends HttpQueryContext {
	public function __construct( private QueryRunnerInterface $mock_qr ) {
		parent::__construct( MockDataSource::from_array( MockDataSource::MOCK_CONFIG, new MockValidator() ) );
	}

	public function get_query_runner(): QueryRunnerInterface {
		return $this->mock_qr;
	}
}

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
			static function ( $errno, $errstr ) {
				throw new \Exception( $errstr, $errno ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			},
			E_USER_WARNING
		);

		$GLOBALS['__wordpress_done_actions'] = [];

		ConfigRegistry::init();
	}

	protected function tearDown(): void {
		\restore_error_handler();
	}

	public function testResolveRemoteDataSimple() {
		$expected1 = 'Happy happy hour! No networking!';
		$expected2 = 'Comedor del Presidente';

		$mock_qr = new MockQueryRunner();
		$mock_qr->addResult( 'title', $expected1 );
		$mock_qr->addResult( 'location', $expected2 );

		$mock_query_context = new TestQueryContext( $mock_qr );
		register_remote_data_block( 'Events', $mock_query_context );

		$result = VipBlockDataApi::resolve_remote_data( self::$sourced_block1, 'remote-data-blocks/events', 12, self::$parsed_block1, $mock_qr );
		$this->assertSame( $expected1, $result['innerBlocks'][0]['attributes']['content'] );
		$this->assertSame( $expected2, $result['innerBlocks'][1]['attributes']['content'] );
	}

	public function testResolveRemoteDataPassesThroughUnregisteredBlocks() {
		$result = VipBlockDataApi::resolve_remote_data( self::$sourced_block1, 'remote-data-blocks/events', 12, self::$parsed_block1 );
		$this->assertSame( 'Happy hour &amp; networking', $result['innerBlocks'][0]['attributes']['content'] );
		$this->assertSame( "President's dining hall", $result['innerBlocks'][1]['attributes']['content'] );
	}

	public function testResolveRemoteDataFallsBackToDbOnQuery() {
		$mock_qr = new MockQueryRunner();
		$mock_qr->addResult( 'title', 'Happy happy hour! No networking!' );
		$mock_qr->addResult( 'location', new \WP_Error( 'rdb-uh-oh', 'uh-oh!' ) );

		$mock_query_context = new TestQueryContext( $mock_qr );
		register_remote_data_block( 'Events', $mock_query_context );

		$result = VipBlockDataApi::resolve_remote_data( self::$sourced_block1, 'remote-data-blocks/events', 12, self::$parsed_block1, $mock_qr );

		$this->assertSame(
			[
				'remote-data-blocks',
				'error',
				'Error executing query for block binding: uh-oh! remote-data-blocks/remoteData (block: remote-data-blocks/events; operation: location)',
				[],
			],
			$GLOBALS['__wordpress_done_actions']['wpcomvip_log'][0],
		);
		$this->assertSame( 'Happy happy hour! No networking!', $result['innerBlocks'][0]['attributes']['content'] );
		$this->assertSame( "President's dining hall", $result['innerBlocks'][1]['attributes']['content'] );
	}
}
