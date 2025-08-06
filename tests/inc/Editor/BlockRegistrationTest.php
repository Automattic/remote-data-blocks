<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Editor\BlockManagement;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use WP_Error;

/**
 * Test class for BlockRegistration functionality.
 */
class BlockRegistrationTest extends TestCase {

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		ConfigStore::init();
		ConfigRegistry::init();
	}

	/**
	 * Test that block registration works with explicit name provided.
	 */
	public function test_register_block_with_explicit_name(): void {
		$test_query_runner = $this->get_query_runner_with_response( [] );

		$test_data_source = HttpDataSource::from_array( [
			'__version' => 1,
			'display_name' => 'Test API',
			'endpoint' => 'https://example.com/test-api',
		] );

		$test_query = HttpQuery::from_array( [
			'data_source' => $test_data_source,
			'query_runner' => $test_query_runner,
			'output_schema' => [
				'is_collection' => false,
				'type' => [
					'title' => [
						'name' => 'Title',
						'path' => '$.title',
						'type' => 'string',
					],
				],
			],
		] );

		// Register block with explicit name
		$registration_result = register_remote_data_block( [
			'title' => 'My Test Block',
			'name' => 'custom-block-name',
			'render_query' => [
				'query' => $test_query,
			],
		] );

		$this->assertTrue( $registration_result );

		// Verify the block was registered with the expected name
		$expected_block_name = 'remote-data-blocks/custom-block-name';
		$this->assertTrue( ConfigStore::is_registered_block( $expected_block_name ) );

		// Verify the block configuration is accessible
		$block_config = ConfigStore::get_block_configuration( $expected_block_name );
		$this->assertIsArray( $block_config );
		$this->assertEquals( 'My Test Block', $block_config['title'] );
		$this->assertEquals( $expected_block_name, $block_config['name'] );
	}

	/**
	 * Test that block registration works without explicit name (fallback to title).
	 */
	public function test_register_block_without_explicit_name(): void {
		$test_query_runner = $this->get_query_runner_with_response( [] );

		$test_data_source = HttpDataSource::from_array( [
			'__version' => 1,
			'display_name' => 'Test API',
			'endpoint' => 'https://example.com/test-api',
		] );

		$test_query = HttpQuery::from_array( [
			'data_source' => $test_data_source,
			'query_runner' => $test_query_runner,
			'output_schema' => [
				'is_collection' => false,
				'type' => [
					'title' => [
						'name' => 'Title',
						'path' => '$.title',
						'type' => 'string',
					],
				],
			],
		] );

		// Register block without explicit name
		$registration_result = register_remote_data_block( [
			'title' => 'Another Test Block',
			'render_query' => [
				'query' => $test_query,
			],
		] );

		$this->assertTrue( $registration_result );

		// Verify the block was registered with name derived from title
		$expected_block_name = 'remote-data-blocks/another-test-block';
		$this->assertTrue( ConfigStore::is_registered_block( $expected_block_name ) );

		// Verify the block configuration is accessible
		$block_config = ConfigStore::get_block_configuration( $expected_block_name );
		$this->assertIsArray( $block_config );
		$this->assertEquals( 'Another Test Block', $block_config['title'] );
		$this->assertEquals( $expected_block_name, $block_config['name'] );
	}

	/**
	 * Test that block registration with explicit name handles special characters correctly.
	 */
	public function test_register_block_with_explicit_name_special_characters(): void {
		$test_query_runner = $this->get_query_runner_with_response( [] );

		$test_data_source = HttpDataSource::from_array( [
			'__version' => 1,
			'display_name' => 'Test API',
			'endpoint' => 'https://example.com/test-api',
		] );

		$test_query = HttpQuery::from_array( [
			'data_source' => $test_data_source,
			'query_runner' => $test_query_runner,
			'output_schema' => [
				'is_collection' => false,
				'type' => [
					'title' => [
						'name' => 'Title',
						'path' => '$.title',
						'type' => 'string',
					],
				],
			],
		] );

		// Register block with explicit name containing special characters
		$registration_result = register_remote_data_block( [
			'title' => 'Special Characters Block',
			'name' => 'special-chars-block!@#$%^&*()',
			'render_query' => [
				'query' => $test_query,
			],
		] );

		$this->assertTrue( $registration_result );

		// Debug: Check what block names are actually registered
		$all_blocks = ConfigStore::get_block_configurations();

		// Find the block that was just registered
		$registered_block = null;
		foreach ( $all_blocks as $block_name => $config ) {
			if ( 'Special Characters Block' === $config['title'] ) {
				$registered_block = $block_name;
				break;
			}
		}

		$this->assertNotNull( $registered_block, 'Block should be registered' );

		// Verify the block configuration is accessible
		$block_config = ConfigStore::get_block_configuration( $registered_block );
		$this->assertIsArray( $block_config );
		$this->assertEquals( 'Special Characters Block', $block_config['title'] );
		$this->assertEquals( $registered_block, $block_config['name'] );
	}

	/**
	 * Test that block registration with explicit name works with spaces and underscores.
	 */
	public function test_register_block_with_explicit_name_spaces_and_underscores(): void {
		$test_query_runner = $this->get_query_runner_with_response( [] );

		$test_data_source = HttpDataSource::from_array( [
			'__version' => 1,
			'display_name' => 'Test API',
			'endpoint' => 'https://example.com/test-api',
		] );

		$test_query = HttpQuery::from_array( [
			'data_source' => $test_data_source,
			'query_runner' => $test_query_runner,
			'output_schema' => [
				'is_collection' => false,
				'type' => [
					'title' => [
						'name' => 'Title',
						'path' => '$.title',
						'type' => 'string',
					],
				],
			],
		] );

		// Register block with explicit name containing spaces and underscores
		$registration_result = register_remote_data_block( [
			'title' => 'Spaces and Underscores Block',
			'name' => 'spaces_and_underscores_block',
			'render_query' => [
				'query' => $test_query,
			],
		] );

		$this->assertTrue( $registration_result );

		// Verify the block was registered with sanitized name
		$expected_block_name = 'remote-data-blocks/spaces-and-underscores-block';
		$this->assertTrue( ConfigStore::is_registered_block( $expected_block_name ) );

		// Verify the block configuration is accessible
		$block_config = ConfigStore::get_block_configuration( $expected_block_name );
		$this->assertIsArray( $block_config );
		$this->assertEquals( 'Spaces and Underscores Block', $block_config['title'] );
		$this->assertEquals( $expected_block_name, $block_config['name'] );
	}

	/**
	 * Test that block registration with explicit name removes slashes correctly.
	 */
	public function test_register_block_with_explicit_name_removes_slashes(): void {
		$test_query_runner = $this->get_query_runner_with_response( [] );

		$test_data_source = HttpDataSource::from_array( [
			'__version' => 1,
			'display_name' => 'Test API',
			'endpoint' => 'https://example.com/test-api',
		] );

		$test_query = HttpQuery::from_array( [
			'data_source' => $test_data_source,
			'query_runner' => $test_query_runner,
			'output_schema' => [
				'is_collection' => false,
				'type' => [
					'title' => [
						'name' => 'Title',
						'path' => '$.title',
						'type' => 'string',
					],
				],
			],
		] );

		// Register block with explicit name containing slashes
		$registration_result = register_remote_data_block( [
			'title' => 'Slash Test Block',
			'name' => 'category/subcategory/block-name',
			'render_query' => [
				'query' => $test_query,
			],
		] );

		$this->assertTrue( $registration_result );

		// Verify the block was registered with slashes removed and proper prefix
		$expected_block_name = 'remote-data-blocks/category-subcategory-block-name';
		$this->assertTrue( ConfigStore::is_registered_block( $expected_block_name ) );

		// Verify the block configuration is accessible
		$block_config = ConfigStore::get_block_configuration( $expected_block_name );
		$this->assertIsArray( $block_config );
		$this->assertEquals( 'Slash Test Block', $block_config['title'] );
		$this->assertEquals( $expected_block_name, $block_config['name'] );

		// Verify there are no additional slashes in the final name
		$this->assertStringNotContainsString( '//', $block_config['name'], 'Block name should not contain double slashes' );
		$this->assertStringStartsWith( 'remote-data-blocks/', $block_config['name'], 'Block name should start with remote-data-blocks/' );
		$this->assertStringNotContainsString( '/', substr( $block_config['name'], 20 ), 'Block name should not contain slashes after the prefix' );
	}

	/**
	 * Test that block registration fails when trying to register the same explicit name twice.
	 */
	public function test_register_block_duplicate_explicit_name_fails(): void {
		$test_query_runner = $this->get_query_runner_with_response( [] );

		$test_data_source = HttpDataSource::from_array( [
			'__version' => 1,
			'display_name' => 'Test API',
			'endpoint' => 'https://example.com/test-api',
		] );

		$test_query = HttpQuery::from_array( [
			'data_source' => $test_data_source,
			'query_runner' => $test_query_runner,
			'output_schema' => [
				'is_collection' => false,
				'type' => [
					'title' => [
						'name' => 'Title',
						'path' => '$.title',
						'type' => 'string',
					],
				],
			],
		] );

		// Register first block with explicit name
		$registration_result1 = register_remote_data_block( [
			'title' => 'First Block',
			'name' => 'duplicate-name',
			'render_query' => [
				'query' => $test_query,
			],
		] );

		$this->assertTrue( $registration_result1 );

		// Try to register second block with same explicit name
		$registration_result2 = register_remote_data_block( [
			'title' => 'Second Block',
			'name' => 'duplicate-name',
			'render_query' => [
				'query' => $test_query,
			],
		] );

		$this->assertInstanceOf( WP_Error::class, $registration_result2 );
		$this->assertEquals( 'block_registration_error', $registration_result2->get_error_code() );
	}

	/**
	 * Test that block registration with explicit name works correctly with BlockRegistration.
	 */
	public function test_block_registration_with_explicit_name(): void {
		$test_query_runner = $this->get_query_runner_with_response( [] );

		$test_data_source = HttpDataSource::from_array( [
			'__version' => 1,
			'display_name' => 'Test API',
			'endpoint' => 'https://example.com/test-api',
		] );

		$test_query = HttpQuery::from_array( [
			'data_source' => $test_data_source,
			'query_runner' => $test_query_runner,
			'output_schema' => [
				'is_collection' => false,
				'type' => [
					'title' => [
						'name' => 'Title',
						'path' => '$.title',
						'type' => 'string',
					],
				],
			],
		] );

		// Register block with explicit name
		$registration_result = register_remote_data_block( [
			'title' => 'Test Block for Registration',
			'name' => 'test-registration-block',
			'render_query' => [
				'query' => $test_query,
			],
		] );

		$this->assertTrue( $registration_result );

		// Get the block configuration
		$block_name = 'remote-data-blocks/test-registration-block';
		$block_config = ConfigStore::get_block_configuration( $block_name );
		$this->assertIsArray( $block_config );

		// Test that the configuration has the expected structure
		$this->assertEquals( $block_name, $block_config['name'] );
		$this->assertEquals( 'Test Block for Registration', $block_config['title'] );
		$this->assertArrayHasKey( 'queries', $block_config );
		$this->assertArrayHasKey( 'selectors', $block_config );
	}

	/**
	 * Create a mock query runner for testing.
	 *
	 * @param array $response_data The response data to return.
	 * @param int   $status_code   The HTTP status code.
	 */
	private function get_query_runner_with_response( array $response_data, int $status_code = 200 ): QueryRunner {
		return new class($response_data, $status_code) extends QueryRunner {
			private $response_data;
			private $status_code;

			public function __construct( array $response_data, int $status_code ) {
				parent::__construct( null, [] );

				$this->response_data = $response_data;
				$this->status_code = $status_code;
			}

			protected function get_raw_response_data( array $request_details, array $input_variables ): array|WP_Error {
				return [
					'metadata' => [
						'age' => 100,
						'status_code' => $this->status_code,
					],
					'response_data' => $this->response_data,
				];
			}
		};
	}
}
