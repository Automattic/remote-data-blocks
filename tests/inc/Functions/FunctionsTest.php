<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Editor\BlockManagement;

use Psr\Log\LogLevel;
use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Editor\BlockManagement\ConfigRegistry;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\Tests\Mocks\MockLogger;
use RemoteDataBlocks\Tests\Mocks\MockDatasource;
use RemoteDataBlocks\Tests\Mocks\MockValidator;

use function register_remote_data_block;
use function register_remote_data_list_query;
use function register_remote_data_search_query;
use function register_remote_data_loop_block;

class FunctionsTest extends TestCase {
	private MockLogger $mock_logger;
	private MockDatasource $mock_datasource;

	protected function setUp(): void {
		parent::setUp();
		$this->mock_logger = new MockLogger();
		$this->mock_datasource = MockDatasource::from_array( MockDatasource::MOCK_CONFIG, new MockValidator() );
		ConfigRegistry::init( $this->mock_logger );
	}

	public function testRegisterBlock() {
		$query_context = new HttpQueryContext( $this->mock_datasource );
		register_remote_data_block( 'Test Block', $query_context );

		$block_name = 'remote-data-blocks/test-block';
		$this->assertTrue( ConfigStore::is_registered_block( $block_name ) );

		$config = ConfigStore::get_configuration( $block_name );
		$this->assertIsArray( $config );
		$this->assertSame( $block_name, $config['name'] );
		$this->assertSame( 'Test Block', $config['title'] );
		$this->assertFalse( $config['loop'] );
	}

	public function testRegisterLoopBlock() {
		$query_context = new HttpQueryContext( $this->mock_datasource );
		register_remote_data_loop_block( 'Loop Block', $query_context );

		$block_name = 'remote-data-blocks/loop-block';
		$this->assertTrue( ConfigStore::is_registered_block( $block_name ) );

		$config = ConfigStore::get_configuration( $block_name );
		$this->assertIsArray( $config );
		$this->assertTrue( $config['loop'] );
	}

	public function testRegisterQuery() {
		$query_context = new HttpQueryContext( $this->mock_datasource );
		register_remote_data_block( 'Query Block', $query_context );

		$additional_query = new HttpQueryContext( $this->mock_datasource );
		ConfigRegistry::register_query( 'Query Block', $additional_query );

		$block_name = 'remote-data-blocks/query-block';
		$config = ConfigStore::get_configuration( $block_name );
		$this->assertArrayHasKey( get_class( $additional_query ), $config['queries'] );
	}

	public function testRegisterListQuery() {
		$query_context = new HttpQueryContext( $this->mock_datasource );
		register_remote_data_block( 'List Block', $query_context );

		$list_query = new HttpQueryContext(
			$this->mock_datasource,
			[],
			[ 'mappings' => [ 'test' => 'test' ] ]
		);
		register_remote_data_list_query( 'List Block', $list_query );

		$block_name = 'remote-data-blocks/list-block';
		$config = ConfigStore::get_configuration( $block_name );
		$this->assertSame( 'list', $config['selectors'][0]['type'] );
	}

	public function testRegisterSearchQuery() {
		$query_context = new HttpQueryContext( $this->mock_datasource );
		register_remote_data_block( 'Search Block', $query_context );

		$search_query = new HttpQueryContext(
			$this->mock_datasource,
			[ 'search_terms' => [ 'type' => 'string' ] ],
			[ 'mappings' => [ 'test' => 'test' ] ]
		);
		register_remote_data_search_query( 'Search Block', $search_query );

		$block_name = 'remote-data-blocks/search-block';
		$config = ConfigStore::get_configuration( $block_name );
		$this->assertSame( 'search', $config['selectors'][0]['type'] );
	}

	public function testGetBlockNames() {
		register_remote_data_block( 'Block One', new HttpQueryContext( $this->mock_datasource ) );
		register_remote_data_block( 'Block Two', new HttpQueryContext( $this->mock_datasource ) );

		$block_names = ConfigStore::get_block_names();
		$this->assertCount( 2, $block_names );
		$this->assertContains( 'remote-data-blocks/block-one', $block_names );
		$this->assertContains( 'remote-data-blocks/block-two', $block_names );
	}

	public function testIsRegisteredBlockReturnsTrueForRegisteredBlock() {
		register_remote_data_block( 'Some Slick Block', new HttpQueryContext( $this->mock_datasource ) );
		$this->assertTrue( ConfigStore::is_registered_block( 'remote-data-blocks/some-slick-block' ) );
	}

	public function testIsRegisteredBlockReturnsFalseWhenNoConfigurations() {
		$this->assertFalse( ConfigStore::is_registered_block( 'nonexistent' ) );
	}

	public function testGetConfigurationForNonexistentBlock() {
		$this->assertNull( ConfigStore::get_configuration( 'nonexistent' ) );
		$this->assertTrue( $this->mock_logger->hasLoggedLevel( LogLevel::ERROR ) );
		$error_logs = $this->mock_logger->getLogsByLevel( LogLevel::ERROR );
		$this->assertStringContainsString( 'not been registered', $error_logs[0]['message'] );
	}

	public function testRegisterDuplicateBlock() {
		$query_context = new HttpQueryContext( $this->mock_datasource );
		register_remote_data_block( 'Duplicate Block', $query_context );
		register_remote_data_block( 'Duplicate Block', $query_context );

		$this->assertTrue( $this->mock_logger->hasLoggedLevel( LogLevel::ERROR ) );
		$error_logs = $this->mock_logger->getLogsByLevel( LogLevel::ERROR );
		$this->assertStringContainsString( 'already been registered', $error_logs[0]['message'] );
	}

	public function testRegisterSearchQueryWithoutSearchTerms() {
		$query_context = new HttpQueryContext( $this->mock_datasource );
		register_remote_data_block( 'Invalid Search Block', $query_context );

		$search_query = new HttpQueryContext( $this->mock_datasource );
		register_remote_data_search_query( 'Invalid Search Block', $search_query );

		$this->assertTrue( $this->mock_logger->hasLoggedLevel( LogLevel::ERROR ) );
		$error_logs = $this->mock_logger->getLogsByLevel( LogLevel::ERROR );
		$this->assertStringContainsString( 'search_terms', $error_logs[0]['message'] );
	}
}
