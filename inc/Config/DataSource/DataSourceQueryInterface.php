<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\DataSource;

use RemoteDataBlocks\Config\ArraySerializableInterface;

/**
 * Interface for data source query configurations.
 * 
 * This interface represents the query-specific parameters for a data source,
 * separate from the connection/authentication information.
 */
interface DataSourceQueryInterface extends ArraySerializableInterface {
	public function get_uuid(): string;
	public function get_connection_uuid(): string|null;
	public function get_display_name(): string;
	public static function get_service_name(): string;
	public function get_service_config(): array;
}
