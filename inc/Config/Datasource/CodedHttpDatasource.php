<?php

namespace RemoteDataBlocks\Config\Datasource;

/**
 * CodedHttpDatasource class
 *
 * Extends HttpDatasource to define a HTTP datasource that can be displayed in
 * the plugin settings.
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */
abstract class CodedHttpDatasource extends HttpDatasource {
	/**
	 * Provide object representations of the data source for display in plugin
	 * settings. This allows the data sources defined in code to be viewed without
	 * being subject to CRUD operations.
	 *
	 * @return array An array of object representations.
	 */
	abstract public function get_object_representations(): array;
}
