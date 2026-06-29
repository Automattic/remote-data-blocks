<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Docs;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Editor\BlockManagement\ConfigRegistry;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\Tests\Mocks\MockLogger;
use RemoteDataBlocks\Tests\Mocks\MockWordPressFunctions;
use RemoteDataBlocks\WpdbStorage\DataSourceCrud;

use function register_remote_data_block;

class HttpTutorialExampleTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		ConfigRegistry::init( new MockLogger() );
	}

	protected function tearDown(): void {
		MockWordPressFunctions::reset();
	}

	public function test_http_data_source_tutorial_example_registers_block(): void {
		$data_source_config = DataSourceCrud::create_config( [
			'service' => REMOTE_DATA_BLOCKS_GENERIC_HTTP_SERVICE,
			'service_config' => [
				'__version' => 1,
				'auth' => [
					'type' => 'none',
					'value' => '',
				],
				'display_name' => 'Zip Code API',
				'endpoint' => 'https://api.zippopotam.us/us/',
			],
			'uuid' => '00000000-0000-4000-8000-000000000001',
		] );

		$this->assertIsArray( $data_source_config );

		$zip_code_data_source = HttpDataSource::from_uuid( $data_source_config['uuid'] );

		if ( is_wp_error( $zip_code_data_source ) ) {
			$this->fail( $zip_code_data_source->get_error_message() );
		}

		$zip_code_query = [
			'data_source' => $zip_code_data_source,
			'display_name' => 'Get location by Zip code',
			'endpoint' => function ( array $input_variables ) use ( $zip_code_data_source ): string {
				return $zip_code_data_source->get_endpoint() . $input_variables['zip_code'];
			},
			'input_schema' => [
				'zip_code' => [
					'name' => 'Zip Code',
					'type' => 'string',
				],
			],
			'output_schema' => [
				'is_collection' => false,
				'type' => [
					'zip_code' => [
						'name' => 'Zip Code',
						'path' => '$["post code"]',
						'type' => 'string',
					],
					'city' => [
						'name' => 'City',
						'path' => '$.places[0]["place name"]',
						'type' => 'string',
					],
					'state' => [
						'name' => 'State',
						'path' => '$.places[0].state',
						'type' => 'string',
					],
				],
			],
		];

		$registration_result = register_remote_data_block( [
			'title' => 'Zip Code',
			'render_query' => [
				'query' => $zip_code_query,
			],
		] );

		$this->assertTrue( $registration_result );

		$block_config = ConfigStore::get_block_configuration( 'remote-data-blocks/zip-code' );
		$this->assertIsArray( $block_config );
		$this->assertInstanceOf( HttpQuery::class, $block_config['queries']['display'] );
		$this->assertSame(
			'https://api.zippopotam.us/us/90210',
			$block_config['queries']['display']->get_endpoint( [ 'zip_code' => '90210' ] )
		);
	}
}
