<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\DataSource;

use RemoteDataBlocks\Config\ArraySerializableInterface;

/**
 * Interface for data source connection configurations.
 * 
 * This interface represents the authentication and connection information
 * for a data source, separate from the query-specific parameters.
 */
interface DataSourceConnectionInterface extends ArraySerializableInterface {
	public function get_uuid(): string;
	public function get_display_name(): string;
	public static function get_service_name(): string;
	public function get_service_config(): array;
}
