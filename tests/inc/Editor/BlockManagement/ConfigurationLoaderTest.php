<?php

namespace RemoteDataBlocks\Tests\Editor\BlockManagement;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Editor\BlockManagement\ConfigurationLoader;
use RemoteDataBlocks\Tests\Mocks\MockLogger;
use RemoteDataBlocks\Tests\Mocks\MockDatasource;
use Psr\Log\LogLevel;

class ConfigurationLoaderTest extends TestCase {
    private MockLogger $mockLogger;

    protected function setUp(): void {
        parent::setUp();
        $this->mockLogger = new MockLogger();
        ConfigurationLoader::init($this->mockLogger);
    }

	protected function tearDown(): void {
		ConfigurationLoader::unregister_all();
		parent::tearDown();
	}

    public function testIsRegisteredBlockReturnsFalseWhenNoConfigurations() {
        $this->assertFalse(ConfigurationLoader::is_registered_block('nonexistent'));
    }

    public function testIsRegisteredBlockReturnsTrueForRegisteredBlock() {
        ConfigurationLoader::register_block('some_slick_block', new HttpQueryContext(new MockDatasource()));
        $this->assertTrue(ConfigurationLoader::is_registered_block('remote-data-blocks/some_slick_block'));
    }

    public function testRegisterBlock() {
        $queryContext = new HttpQueryContext(new MockDatasource());
        ConfigurationLoader::register_block('Test Block', $queryContext);

        $blockName = 'remote-data-blocks/test-block';
        $this->assertTrue(ConfigurationLoader::is_registered_block($blockName));

        $config = ConfigurationLoader::get_configuration($blockName);
        $this->assertNotNull($config);
        $this->assertEquals($blockName, $config['name']);
        $this->assertEquals('Test Block', $config['title']);
        $this->assertFalse($config['loop']);
        $this->assertArrayHasKey('__DISPLAY__', $config['queries']);
    }

    public function testRegisterLoopBlock() {
        $queryContext = new HttpQueryContext(new MockDatasource());
        ConfigurationLoader::register_loop_block('Loop Block', $queryContext);

        $blockName = 'remote-data-blocks/loop-block';
        $this->assertTrue(ConfigurationLoader::is_registered_block($blockName));

        $config = ConfigurationLoader::get_configuration($blockName);
        $this->assertNotNull($config);
        $this->assertTrue($config['loop']);
    }

    public function testRegisterBlockPattern() {
        $queryContext = new HttpQueryContext(new MockDatasource());
        ConfigurationLoader::register_block('Pattern Block', $queryContext);
        ConfigurationLoader::register_block_pattern('Pattern Block', 'Test Pattern', '<!-- wp:paragraph -->Test<!-- /wp:paragraph -->');

        $blockName = 'remote-data-blocks/pattern-block';
        $config = ConfigurationLoader::get_configuration($blockName);
        $this->assertArrayHasKey('patterns', $config);
        $this->assertArrayHasKey('Test Pattern', $config['patterns']);
    }

    public function testRegisterPage() {
        $queryContext = new HttpQueryContext(new MockDatasource());
        $queryContext->input_variables = ['test_var' => ['type' => 'string']];
        ConfigurationLoader::register_block('Page Block', $queryContext);
        ConfigurationLoader::register_page('Page Block', 'test-page');

        $blockName = 'remote-data-blocks/page-block';
        $config = ConfigurationLoader::get_configuration($blockName);
        $this->assertArrayHasKey('overrides', $config['queries']['__DISPLAY__']->input_variables['test_var']);
    }

    public function testRegisterQuery() {
        $queryContext = new HttpQueryContext(new MockDatasource());
        ConfigurationLoader::register_block('Query Block', $queryContext);
        
        $additionalQuery = new HttpQueryContext(new MockDatasource());
        ConfigurationLoader::register_query('Query Block', $additionalQuery);

        $blockName = 'remote-data-blocks/query-block';
        $config = ConfigurationLoader::get_configuration($blockName);
        $this->assertArrayHasKey(get_class($additionalQuery), $config['queries']);
    }

    public function testRegisterListQuery() {
        $queryContext = new HttpQueryContext(new MockDatasource());
        ConfigurationLoader::register_block('List Block', $queryContext);
        
        $listQuery = new HttpQueryContext(new MockDatasource());
        $listQuery->output_variables['mappings'] = ['test' => 'test'];
        ConfigurationLoader::register_list_query('List Block', $listQuery);

        $blockName = 'remote-data-blocks/list-block';
        $config = ConfigurationLoader::get_configuration($blockName);
        $this->assertEquals('list', $config['selectors'][0]['type']);
    }

    public function testRegisterSearchQuery() {
        $queryContext = new HttpQueryContext(new MockDatasource());
        ConfigurationLoader::register_block('Search Block', $queryContext);
        
        $searchQuery = new HttpQueryContext(new MockDatasource());
        $searchQuery->input_variables['search_terms'] = ['type' => 'string'];
        $searchQuery->output_variables['mappings'] = ['test' => 'test'];
        ConfigurationLoader::register_search_query('Search Block', $searchQuery);

        $blockName = 'remote-data-blocks/search-block';
        $config = ConfigurationLoader::get_configuration($blockName);
        $this->assertEquals('search', $config['selectors'][0]['type']);
    }

    public function testGetBlockNames() {
        ConfigurationLoader::register_block('Block One', new HttpQueryContext(new MockDatasource()));
        ConfigurationLoader::register_block('Block Two', new HttpQueryContext(new MockDatasource()));

        $blockNames = ConfigurationLoader::get_block_names();
        $this->assertCount(2, $blockNames);
        $this->assertContains('remote-data-blocks/block-one', $blockNames);
        $this->assertContains('remote-data-blocks/block-two', $blockNames);
    }

    public function testGetConfigurationForNonexistentBlock() {
        $config = ConfigurationLoader::get_configuration('nonexistent');
        $this->assertNull($config);
        $this->assertTrue($this->mockLogger->hasLoggedLevel(LogLevel::ERROR));
    }

    public function testRegisterDuplicateBlock() {
        $queryContext = new HttpQueryContext(new MockDatasource());
        ConfigurationLoader::register_block('Duplicate Block', $queryContext);
        ConfigurationLoader::register_block('Duplicate Block', $queryContext);

        $this->assertTrue($this->mockLogger->hasLoggedLevel(LogLevel::ERROR));
        $errorLogs = $this->mockLogger->getLogsByLevel(LogLevel::ERROR);
        $this->assertStringContainsString('has already been registered', $errorLogs[0]['message']);
    }

    public function testRegisterPageWithoutInputVariables() {
        $queryContext = new HttpQueryContext(new MockDatasource());
        ConfigurationLoader::register_block('No Input Block', $queryContext);
        ConfigurationLoader::register_page('No Input Block', 'test-page');

        $this->assertTrue($this->mockLogger->hasLoggedLevel(LogLevel::ERROR));
        $errorLogs = $this->mockLogger->getLogsByLevel(LogLevel::ERROR);
        $this->assertStringContainsString('A page is only useful for queries with input variables', $errorLogs[0]['message']);
    }

    public function testRegisterSearchQueryWithoutSearchTerms() {
        $queryContext = new HttpQueryContext(new MockDatasource());
        ConfigurationLoader::register_block('Invalid Search Block', $queryContext);
        
        $searchQuery = new HttpQueryContext(new MockDatasource());
        ConfigurationLoader::register_search_query('Invalid Search Block', $searchQuery);

        $this->assertTrue($this->mockLogger->hasLoggedLevel(LogLevel::ERROR));
        $errorLogs = $this->mockLogger->getLogsByLevel(LogLevel::ERROR);
        $this->assertStringContainsString('A search query must have a "search_terms" input variable', $errorLogs[0]['message']);
    }
}
