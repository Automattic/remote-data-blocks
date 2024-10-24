<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Data;

use Psr\Log\LoggerInterface;
use RemoteDataBlocks\Config\Datasource\DatasourceInterface;
use RemoteDataBlocks\Logging\LoggerManager;

defined( 'ABSPATH' ) || exit();

/**
 * Class DataSourceRepository
 * 
 * This class is responsible for managing data sources.
 * 
 * It implements the DataSourceRepositoryInterface, DataSourceProviderInterface, and DataSourceWriterInterface interfaces.
 * 
 * @see DataSourceRepositoryInterface
 * @see DataSourceProviderInterface
 * @see DataSourceWriterInterface
 */
class DataSourceRepository implements DataSourceRepositoryInterface, DataSourceProviderInterface, DataSourceWriterInterface {
	/**
	 * @var array<string, DataSourceProviderInterface>
	 */
	private static array $data_sources_providers;

	/**
	 * @var array<string, DataSourceWriterInterface>
	 */
	private static array $data_sources_writers;

	public function __construct( private LoggerInterface $logger ) {
		$this->logger = $logger ?? LoggerManager::instance();
	}

	/**
	 * @param DataSourceProviderInterface[] $data_source_providers
	 * @param DataSourceWriterInterface[] $data_source_writers
	 */
	public static function init( array $data_source_providers, array $data_source_writers ): void {
		self::$data_sources_providers = $data_source_providers;
		self::$data_sources_writers   = $data_source_writers;
	}

	public static function get_data_sources(): array {
		$data_sources = [];

		foreach ( self::$data_sources_providers as $data_source_provider ) {
			$provider_sources = $data_source_provider::get_data_sources();

			foreach ( $provider_sources as $source ) {
				$slug                  = $source->get_slug();
				$data_sources[ $slug ] = $source;
			}
		}

		return $data_sources;
	}

	public static function get( string $slug ): ?DatasourceInterface {
		foreach ( self::$data_sources_providers as $data_source_provider ) {
			$source = $data_source_provider::get( $slug );
			if ( $source ) {
				return $source;
			}
		}

		return null;
	}

	public static function find_by( array $criteria ): array {
		$results = [];

		foreach ( self::$data_sources_providers as $data_source_provider ) {
			$results = array_merge( $results, $data_source_provider::find_by( $criteria ) );
		}

		return $results;
	}  

	public function insert( DatasourceInterface $datasource ): bool {
		$writer = $this->resolve_data_source_writer( $datasource );

		if ( ! $writer ) {
			$this->logger->error( 'Delete attempt on non-writable source!', [ 'datasource' => $datasource::class ] );
			return false;
		}
		
		return $writer->insert( $datasource );
	}

	public function update( DatasourceInterface $datasource ): bool {
		$writer = $this->resolve_data_source_writer( $datasource );

		if ( ! $writer ) {
			$this->logger->error( 'Delete attempt on non-writable source!', [ 'datasource' => $datasource::class ] );
			return false;
		}
		
		return $writer->update( $datasource );
	}

	public function delete( DatasourceInterface $datasource ): bool {
		$writer = $this->resolve_data_source_writer( $datasource );

		if ( ! $writer ) {
			$this->logger->error( 'Delete attempt on non-writable source!', [ 'datasource' => $datasource::class ] );
			return false;
		}

		return $writer->delete( $datasource );      
	}

	public static function is_responsible_for_data_source( DatasourceInterface $datasource ): bool {
		// every datasource write comes through here
		return true;
	}

	private function resolve_data_source_writer( DatasourceInterface $datasource ): ?DataSourceWriterInterface {
		foreach ( self::$data_sources_writers as $data_source_writer ) {
			if ( $data_source_writer->is_responsible_for_data_source( $datasource ) ) {
				return $data_source_writer;
			}
		}

		return null;
	}
}
