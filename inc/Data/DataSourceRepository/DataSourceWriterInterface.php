<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Data;

use RemoteDataBlocks\Config\Datasource\DatasourceInterface;

defined( 'ABSPATH' ) || exit();

/**
 * Interface for a data source writer.
 * 
 * A data source writer is responsible for writing data sources to persistent storage.
 */
interface DataSourceWriterInterface {
	public function insert( DatasourceInterface $datasource ): bool;
	public function update( DatasourceInterface $datasource ): bool;
	public function delete( DatasourceInterface $datasource ): bool;
	public static function is_responsible_for_data_source( DatasourceInterface $datasource ): bool;
}
