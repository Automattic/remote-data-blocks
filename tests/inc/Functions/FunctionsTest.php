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
		$this->mock_query = MockQuery::create();
		$this->mock_list_query = MockQuery::create( [
			'output_schema' => [
				'is_collection' => true,
			],
		] );
		$this->mock_search_query = MockQuery::create( [
			'input_schema' => [
				'search' => [ 'type' => 'ui:search_input' ],
			],
		] );

		ConfigRegistry::init( $this->mock_logger );
	}

	public function testRegisterBlock(): void {
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
						'service_config' => [
							'__version' => 1,
							'display_name' => 'Mock Data Source',
							'endpoint' => 'https://example.com/api',
						],
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
		$this->assertSame( 'list', $config['selectors'][0]['type'] ?? null );
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
		$this->assertSame( 'search', $config['selectors'][0]['type'] ?? null );
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

		$this->assertTrue( $this->mock_logger->hasLoggedLevel( LogLevel::ERROR ) );
		$error_logs = $this->mock_logger->getLogsByLevel( LogLevel::ERROR );
		$this->assertStringContainsString( 'ui:search_input', $error_logs[0]['message'] );
	}
}
