<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Editor\BlockManagement;

use Psr\Log\LogLevel;
use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Editor\BlockManagement\ConfigRegistry;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
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
		$this->mock_query = MockQuery::from_array();
		$this->mock_list_query = MockQuery::from_array( [
			'output_schema' => [
				'is_collection' => true,
			],
		] );
		$this->mock_search_query = MockQuery::from_array( [
			'input_schema' => [
				'search_terms' => [ 'type' => 'string' ],
			],
		] );

		ConfigRegistry::init( $this->mock_logger );
	}

	public function testRegisterBlock() {
		register_remote_data_block( [
			'title' => 'Test Block',
			'queries' => [
				'display' => $this->mock_query,
			],
		] );

		$block_name = 'remote-data-blocks/test-block';
		$this->assertTrue( ConfigStore::is_registered_block( $block_name ) );

		$config = ConfigStore::get_block_configuration( $block_name );
		$this->assertIsArray( $config );
		$this->assertSame( $block_name, $config['name'] );
		$this->assertSame( 'Test Block', $config['title'] );
		$this->assertFalse( $config['loop'] );
	}

	public function testRegisterLoopBlock() {
		register_remote_data_block( [
			'title' => 'Loop Block',
			'queries' => [
				'display' => $this->mock_list_query,
			],
			'loop' => true,
		] );

		$block_name = 'remote-data-blocks/loop-block';
		$this->assertTrue( ConfigStore::is_registered_block( $block_name ) );

		$config = ConfigStore::get_block_configuration( $block_name );
		$this->assertIsArray( $config );
		$this->assertTrue( $config['loop'] );
	}

	public function testRegisterListQuery() {
		register_remote_data_block( [
			'title' => 'Test Block with List Query',
			'queries' => [
				'display' => $this->mock_query,
				'list' => $this->mock_list_query,
			],
		] );

		$block_name = 'remote-data-blocks/test-block-with-list-query';
		$config = ConfigStore::get_block_configuration( $block_name );
		$this->assertSame( 'list', $config['selectors'][0]['type'] ?? null );
	}

	public function testRegisterSearchQuery() {
		register_remote_data_block( [
			'title' => 'Test Block with Search Query',
			'queries' => [
				'display' => $this->mock_query,
				'search' => $this->mock_search_query,
			],
		] );

		$block_name = 'remote-data-blocks/test-block-with-search-query';
		$config = ConfigStore::get_block_configuration( $block_name );
		$this->assertSame( 'search', $config['selectors'][0]['type'] ?? null );
	}

	public function testIsRegisteredBlockReturnsTrueForRegisteredBlock() {
		register_remote_data_block( [
			'title' => 'Some Slick Block',
			'queries' => [
				'display' => $this->mock_query,
			],
		] );

		$this->assertTrue( ConfigStore::is_registered_block( 'remote-data-blocks/some-slick-block' ) );
	}

	public function testIsRegisteredBlockReturnsFalseWhenNoConfigurations() {
		$this->assertFalse( ConfigStore::is_registered_block( 'nonexistent' ) );
	}

	public function testGetConfigurationForNonexistentBlock() {
		$this->assertNull( ConfigStore::get_block_configuration( 'nonexistent' ) );
		$this->assertTrue( $this->mock_logger->hasLoggedLevel( LogLevel::ERROR ) );
		$error_logs = $this->mock_logger->getLogsByLevel( LogLevel::ERROR );
		$this->assertStringContainsString( 'not been registered', $error_logs[0]['message'] );
	}

	public function testRegisterDuplicateBlock() {
		register_remote_data_block( [
			'title' => 'Duplicate Block',
			'queries' => [
				'display' => $this->mock_query,
			],
		] );
		register_remote_data_block( [
			'title' => 'Duplicate Block',
			'queries' => [
				'display' => $this->mock_query,
			],
		] );

		$this->assertTrue( $this->mock_logger->hasLoggedLevel( LogLevel::ERROR ) );
		$error_logs = $this->mock_logger->getLogsByLevel( LogLevel::ERROR );
		$this->assertStringContainsString( 'already been registered', $error_logs[0]['message'] );
	}

	public function testRegisterSearchQueryWithoutSearchTerms() {
		register_remote_data_block( [
			'title' => 'Invalid Search Block',
			'queries' => [
				'display' => $this->mock_query,
				'search' => $this->mock_query,
			],
		] );

		$this->assertTrue( $this->mock_logger->hasLoggedLevel( LogLevel::ERROR ) );
		$error_logs = $this->mock_logger->getLogsByLevel( LogLevel::ERROR );
		$this->assertStringContainsString( 'search_terms', $error_logs[0]['message'] );
	}
}
