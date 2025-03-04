<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Store\DataSource;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\DataSource\DataSourceInterface;
use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Store\DataSource\ConstantConfigStore;
use WP_Error;
use Mockery;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ConstantConfigStoreTest extends TestCase {
	private const CONFIG_CONSTANT = 'REMOTE_DATA_BLOCKS_CONFIGS';
	private const TEST_UUID = '123e4567-e89b-12d3-a456-426614174000';
	private const TEST_UUID_2 = '123e4567-e89b-12d3-a456-426614174001';
	private const TEST_SERVICE = 'shopify';
	private const INVALID_SERVICE = 'invalid-service';

	private array $valid_config;
	private array $invalid_config;

	protected function setUp(): void {
		parent::setUp();

		$this->valid_config = [
			'uuid' => self::TEST_UUID,
			'service' => self::TEST_SERVICE,
			'service_config' => [
				'__version' => 1,
				'store_name' => 'test-store',
				'access_token' => 'gy56yrtyrtt',
				'display_name' => 'Test Store',
			],
		];

		$this->invalid_config = [
			'uuid' => self::TEST_UUID_2,
			'service' => self::INVALID_SERVICE,
			'service_config' => [],
		];

		$mock_data_source = Mockery::mock( DataSourceInterface::class );
		$mock_class = Mockery::mock( 'overload:' . HttpDataSource::class );
		$mock_class->shouldReceive( 'from_array' )->andReturn( $mock_data_source );

		if ( ! defined( 'REMOTE_DATA_BLOCKS__DATA_SOURCE_CLASSMAP' ) ) {
			define( 'REMOTE_DATA_BLOCKS__DATA_SOURCE_CLASSMAP', [
				self::TEST_SERVICE => HttpDataSource::class,
			] );
		}
	}

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	private function defineConfigs( mixed $configs ): void {
		if ( ! defined( self::CONFIG_CONSTANT ) ) {
			define( self::CONFIG_CONSTANT, $configs );
		}
	}

	public function testGetConfigsReturnsEmptyArrayWhenConstantNotDefined(): void {
		$this->assertSame( [], ConstantConfigStore::get_configs() );
	}

	public function testGetConfigsReturnsOnlyValidConfigs(): void {
		$this->defineConfigs( [ $this->valid_config, $this->invalid_config ] );

		$configs = ConstantConfigStore::get_configs();
		$this->assertCount( 1, $configs );
		$this->assertSame( $this->valid_config, $configs[0] );
	}

	public function testGetConfigByUuidReturnsErrorWhenConfigNotFound(): void {
		$this->defineConfigs( [] );

		$result = ConstantConfigStore::get_config_by_uuid( self::TEST_UUID );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'data_source_not_found', $result->get_error_code() );
	}

	public function testGetConfigByUuidReturnsErrorForInvalidConfig(): void {
		$invalid_config = [
			'uuid' => self::TEST_UUID,
			'service' => self::INVALID_SERVICE,
			'service_config' => [],
		];

		$this->defineConfigs( [ $invalid_config ] );

		$result = ConstantConfigStore::get_config_by_uuid( self::TEST_UUID );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'unsupported_data_source', $result->get_error_code() );
	}

	public function testGetConfigByUuidReturnsConfigWhenValid(): void {
		$this->defineConfigs( [ $this->valid_config ] );

		$result = ConstantConfigStore::get_config_by_uuid( self::TEST_UUID );
		$this->assertIsArray( $result );
		$this->assertSame( $this->valid_config, $result );
	}
}
