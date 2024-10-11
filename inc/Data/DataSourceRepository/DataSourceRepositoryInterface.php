<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Data;

use RemoteDataBlocks\Config\Datasource\DatasourceInterface;

defined( 'ABSPATH' ) || exit();

interface DataSourceRepositoryInterface {
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

	/**
	 * Insert a new data source.
	 */
	public function insert( DatasourceInterface $datasource ): bool;

	/**
	 * Update an existing data source.
	 */
	public function update( DatasourceInterface $datasource ): bool;

	/**
	 * Delete a data source.
	 */
	public function delete( DatasourceInterface $datasource ): bool;
}
