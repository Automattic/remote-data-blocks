<?php

namespace RemoteDataBlocks\Config;

/**
 * HttpDatasource class
 *
 * Implements the HttpDatasourceInterface to define a generic HTTP datasource.
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */
abstract class HttpDatasource implements DatasourceInterface, HttpDatasourceInterface {
	/**
	 * @inheritdoc
	 */
	abstract public function get_display_name(): string;

	/**
	 * @inheritdoc
	 */
	abstract public function get_uid(): string;

	/**
	 * @inheritdoc
	 */
	abstract public function get_endpoint(): string;

	/**
	 * @inheritdoc
	 */
	abstract public function get_request_headers(): array;

	/**
	 * @inheritdoc
	 */
	public function get_image_url(): ?string {
		return null;
	}
}
