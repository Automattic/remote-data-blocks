<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Data;

use RemoteDataBlocks\Config\Datasource\DatasourceInterface;

defined( 'ABSPATH' ) || exit();

/**
 * Interface for a data source provider.
 * 
 * A data source provider is responsible for providing data sources.
 */
interface DataSourceProviderInterface {
	/**
	 * Get the data sources provided by the implementing class.
	 * 
	 * @return DatasourceInterface[]
	 */
	public static function get_data_sources(): array;

	/**
	 * Get a data source by slug.
	 */
	public static function get( string $slug ): ?DatasourceInterface;

	/**
	 * Find data sources by criteria.
	 * 
	 * @param array $criteria The criteria to search for.
	 * @return DatasourceInterface[]
	 */
	public static function find_by( array $criteria ): array;
}
