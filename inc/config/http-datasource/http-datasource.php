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
	 * @inheritDoc
	 */
	abstract public function get_display_name(): string;

	/**
	 * @inheritDoc
	 */
	abstract public function get_uid(): string;

	/**
	 * @inheritDoc
	 */
	abstract public function get_endpoint(): string;

	/**
	 * @inheritDoc
	 */
	abstract public function get_request_headers(): array;

	/**
	 * @inheritDoc
	 */
	public function get_image_url(): ?string {
		return null;
	}
}
