<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\Query;

use RemoteDataBlocks\WpdbStorage\QueryCrud;
use WP_Error;

/**
 * Default implementation of QueryRegistryInterface.
 */
class QueryRegistry implements QueryRegistryInterface {
	/**
	 * Register a query.
	 *
	 * @param array $config The query configuration.
	 * @return array|WP_Error The registered query or an error.
	 */
	public function register_query( array $config ): array|WP_Error {
		return QueryCrud::create_config( $config );
	}

	/**
	 * Get a query by UUID.
	 *
	 * @param string $uuid The query UUID.
	 * @return array|WP_Error The query configuration or an error.
	 */
	public function get_query_by_uuid( string $uuid ): array|WP_Error {
		return QueryCrud::get_config_by_uuid( $uuid );
	}

	/**
	 * Get all queries.
	 *
	 * @return array List of query configurations.
	 */
	public function get_all_queries(): array {
		return QueryCrud::get_configs();
	}

	/**
	 * Get queries by data source UUID.
	 *
	 * @param string $data_source_uuid The data source UUID.
	 * @return array List of query configurations.
	 */
	public function get_queries_by_data_source( string $data_source_uuid ): array {
		return QueryCrud::get_configs_by_data_source( $data_source_uuid );
	}

	/**
	 * Delete a query by UUID.
	 *
	 * @param string $uuid The query UUID.
	 * @return bool|WP_Error True on success or an error.
	 */
	public function delete_query( string $uuid ): bool|WP_Error {
		return QueryCrud::delete_config_by_uuid( $uuid );
	}

	/**
	 * Update a query by UUID.
	 *
	 * @param string $uuid The query UUID.
	 * @param array $config The new query configuration.
	 * @return array|WP_Error The updated query configuration or an error.
	 */
	public function update_query( string $uuid, array $config ): array|WP_Error {
		return QueryCrud::update_config_by_uuid( $uuid, $config );
	}
}