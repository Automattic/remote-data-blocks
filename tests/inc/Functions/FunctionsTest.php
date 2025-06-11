<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Editor\BlockManagement;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Editor\BlockManagement\ConfigRegistry;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\Logging\LogLevel;
use RemoteDataBlocks\Tests\Mocks\MockLogger;
use RemoteDataBlocks\Tests\Mocks\MockQuery;

use function register_remote_data_block;

class FunctionsTest extends TestCase {
	private MockLogger $mock_logger;
	private MockQuery $mock_query;
	private MockQuery $mock_list_query;
	private MockQuery $mock_search_query;

	protected function setUp(): void {
		parent::setUp();
		$this->mock_logger = new MockLogger();
		$this->mock_query = MockQuery::create( [
			'input_schema' => [
				'id' => [
					'name' => 'ID',
					'type' => 'id',
				],
			],
		] );
		$this->mock_list_query = MockQuery::create( [
			'output_schema' => [
				'is_collection' => true,
				'type' => [
					'id' => [
						'name' => 'ID',
						'path' => '$.id',
						'type' => 'id',
					],
				],
			],
		] );
		$this->mock_search_query = MockQuery::create( [
			'input_schema' => [
				'search' => [ 'type' => 'ui:search_input' ],
			],
			'output_schema' => [
				'type' => [
					'id' => [
						'name' => 'ID',
						'path' => '$.id',
						'type' => 'id',
					],
				],
			],
		] );

		ConfigRegistry::init( $this->mock_logger );
	}

	public function testRegisterBlockWithOldConfigSchema(): void {
		register_remote_data_block( [
			'title' => 'Test Block',
			'render_query' => [
				'query' => $this->mock_query,
			],
		] );

		$block_name = 'remote-data-blocks/test-block';
		$this->assertTrue( ConfigStore::is_registered_block( $block_name ) );

		$config = ConfigStore::get_block_configuration( $block_name );
		$this->assertIsArray( $config );
		$this->assertSame( $block_name, $config['name'] );
		$this->assertSame( 'Test Block', $config['title'] );
	}

	public function testRegisterBlockWithNestedConfig(): void {
		register_remote_data_block( [
			'title' => 'Test Block with Nested Config',
			'render_query' => [
				'query' => [
					'__class' => 'RemoteDataBlocks\Tests\Mocks\MockQuery',
					'data_source' => [
						'__class' => 'RemoteDataBlocks\Tests\Mocks\MockDataSource',
						'display_name' => 'Mock Data Source',
						'endpoint' => 'https://example.com/api',
					],
					'display_name' => 'Mock Query',
					'input_schema' => [],
					'output_schema' => [ 'type' => 'string' ],
				],
			],
		] );

		$block_name = 'remote-data-blocks/test-block-with-nested-config';
		$this->assertTrue( ConfigStore::is_registered_block( $block_name ) );

		$config = ConfigStore::get_block_configuration( $block_name );
		$this->assertIsArray( $config );
		$this->assertSame( $block_name, $config['name'] );
		$this->assertSame( 'Test Block with Nested Config', $config['title'] );
	}

	public function testRegisterListQuery(): void {
		register_remote_data_block( [
			'title' => 'Test Block with List Query',
			'render_query' => [
				'query' => $this->mock_query,
			],
			'selection_queries' => [
				[
					'query' => $this->mock_list_query,
					'type' => 'list',
				],
			],
		] );

		$block_name = 'remote-data-blocks/test-block-with-list-query';
		$config = ConfigStore::get_block_configuration( $block_name );

		// Ensure that display query is the only key in the display_queries_to_selectors.
		$this->assertCount( 1, $config['display_queries_to_selectors'] );
		$this->assertSame( 'display', array_keys( $config['display_queries_to_selectors'] )[0] );

		// Ensure that there are 2 selectors for the display query.
		$this->assertCount( 2, $config['display_queries_to_selectors']['display']['selectors'] );

		// Ensure that the query_key of the first selector is list, and the second one is display.
		$this->assertSame( 'list', $config['display_queries_to_selectors']['display']['selectors'][0]['query_key'] );
		$this->assertSame( 'display', $config['display_queries_to_selectors']['display']['selectors'][1]['query_key'] );
	}

	public function testRegisterSearchQuery(): void {
		register_remote_data_block( [
			'title' => 'Test Block with Search Query',
			'render_query' => [
				'query' => $this->mock_query,
			],
			'selection_queries' => [
				[
					'query' => $this->mock_search_query,
					'type' => 'search',
				],
			],
		] );

		$block_name = 'remote-data-blocks/test-block-with-search-query';
		$config = ConfigStore::get_block_configuration( $block_name );

		// Ensure that display query is the only key in the display_queries_to_selectors.
		$this->assertCount( 1, $config['display_queries_to_selectors'] );
		$this->assertSame( 'display', array_keys( $config['display_queries_to_selectors'] )[0] );

		// Ensure that there are 2 selectors for the display query.
		$this->assertCount( 2, $config['display_queries_to_selectors']['display']['selectors'] );

		// Ensure that the query_key of the first selector is search, and the second one is display.
		$this->assertSame( 'search', $config['display_queries_to_selectors']['display']['selectors'][0]['query_key'] );
		$this->assertSame( 'display', $config['display_queries_to_selectors']['display']['selectors'][1]['query_key'] );
	}

	public function testIsRegisteredBlockReturnsTrueForRegisteredBlock(): void {
		register_remote_data_block( [
			'title' => 'Some Slick Block',
			'render_query' => [
				'query' => $this->mock_query,
			],
		] );

		$this->assertTrue( ConfigStore::is_registered_block( 'remote-data-blocks/some-slick-block' ) );
	}

	public function testIsRegisteredBlockReturnsFalseWhenNoConfigurations(): void {
		$this->assertFalse( ConfigStore::is_registered_block( 'nonexistent' ) );
	}

	public function testGetConfigurationForNonexistentBlock(): void {
		$this->assertNull( ConfigStore::get_block_configuration( 'nonexistent' ) );
		$this->assertTrue( $this->mock_logger->hasLoggedLevel( LogLevel::ERROR ) );
		$error_logs = $this->mock_logger->getLogsByLevel( LogLevel::ERROR );
		$this->assertStringContainsString( 'not been registered', $error_logs[0]['message'] );
	}

	public function testRegisterDuplicateBlock(): void {
		register_remote_data_block( [
			'title' => 'Duplicate Block',
			'render_query' => [
				'query' => $this->mock_query,
			],
		] );
		register_remote_data_block( [
			'title' => 'Duplicate Block',
			'render_query' => [
				'query' => $this->mock_query,
			],
		] );

		$this->assertTrue( $this->mock_logger->hasLoggedLevel( LogLevel::ERROR ) );
		$error_logs = $this->mock_logger->getLogsByLevel( LogLevel::ERROR );
		$this->assertStringContainsString( 'already been registered', $error_logs[0]['message'] );
	}

	public function testRegisterSearchQueryWithoutSearchTerms(): void {
		register_remote_data_block( [
			'title' => 'Invalid Search Block',
			'render_query' => [
				'query' => $this->mock_query,
			],
			'selection_queries' => [
				[
					'query' => $this->mock_query,
					'type' => 'search',
				],
			],
		] );

		$block_name = 'remote-data-blocks/invalid-search-block';
		$config = ConfigStore::get_block_configuration( $block_name );

		// Ensure that display query is the only key in the display_queries_to_selectors.
		$this->assertCount( 1, $config['display_queries_to_selectors'] );
		$this->assertSame( 'display', array_keys( $config['display_queries_to_selectors'] )[0] );

		// Ensure that there is 1 selector for the display query.
		$this->assertCount( 1, $config['display_queries_to_selectors']['display']['selectors'] );

		// Ensure that the query_key of the selector is search.
		$this->assertSame( 'display', $config['display_queries_to_selectors']['display']['selectors'][0]['query_key'] );
	}

	public function testRegisterBlockWithOldSchemaFormatNoRenderQuery(): void {
		register_remote_data_block( [
			'title' => 'Test Block with Old Schema Format No Render Query',
		] );

		$this->assertTrue( $this->mock_logger->hasLoggedLevel( LogLevel::ERROR ) );
		$error_logs = $this->mock_logger->getLogsByLevel( LogLevel::ERROR );
		$this->assertStringContainsString( 'Error registering block Test Block with Old Schema Format No Render Query: Block configuration must have a non-empty "queries" array', $error_logs[0]['message'] );
	}

	public function testRegisterBlockWithNewConfigSchema(): void {
		register_remote_data_block( [
			'title' => 'Test Block with New Config Schema',
			'queries' => [
				'display' => $this->mock_query,
				'search' => $this->mock_search_query,
				'list' => $this->mock_list_query,
			],
			'placeholders' => [
				[
					'name' => 'Get',
					'query_key' => 'display',
				],
				[
					'name' => 'List',
					'query_key' => 'list',
				],
			],
		] );

		$block_name = 'remote-data-blocks/test-block-with-new-config-schema';
		$config = ConfigStore::get_block_configuration( $block_name );

		// Ensure that there are 2 selectors for the display query.
		$this->assertCount( 3, $config['display_queries_to_selectors']['display']['selectors'] );

		// Ensure that the query_key of the first selector is search, the second one is list, and the third one is display.
		$this->assertSame( 'list', $config['display_queries_to_selectors']['display']['selectors'][0]['query_key'] );
		$this->assertSame( 'search', $config['display_queries_to_selectors']['display']['selectors'][1]['query_key'] );
		$this->assertSame( 'display', $config['display_queries_to_selectors']['display']['selectors'][2]['query_key'] );

		// Ensure that there is 1 selector for the list query.
		$this->assertCount( 1, $config['display_queries_to_selectors']['list']['selectors'] );
		$this->assertSame( 'list', $config['display_queries_to_selectors']['list']['selectors'][0]['query_key'] );
	}

	public function testRegisterBlockWithBadDisplayQueries(): void {
		register_remote_data_block( [
			'title' => 'Test Block with Bad Display Queries',
			'queries' => [
				'display' => $this->mock_query,
			],
			'placeholders' => [
				[
					'name' => 'Get',
					'query_key' => 'display',
				],
				[
					'name' => 'List',
					'query_key' => 'list',
				],
				[
					'name' => 'Test',
					'query_key' => 'test',
				],
			],
		] );

		$this->assertTrue( $this->mock_logger->hasLoggedLevel( LogLevel::ERROR ) );
		$error_logs = $this->mock_logger->getLogsByLevel( LogLevel::ERROR );
		$this->assertStringContainsString( 'Error registering block Test Block with Bad Display Queries: Query "list" not found for placeholder "List"', $error_logs[0]['message'] );
	}
}
