<?php

namespace RemoteDataBlocks\Config\Datasource;

/**
 * HttpDatasource class
 *
 * Implements the HttpDatasourceInterface to define a generic HTTP datasource.
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */
abstract class CompatibleHttpDatasource extends HttpDatasource {
	/**
	 * Provide object representations of the data source for display in plugin
	 * settings. This allows the data sources defined in code to be viewed without
	 * being subject to CRUD operations.
	 *
	 * @return array An array of object representations.
	 */
	abstract public function get_object_representations(): array;
}
