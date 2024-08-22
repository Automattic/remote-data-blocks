<?php

/**
 * HttpDatasourceConfig interface
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */

namespace RemoteDataBlocks\Config;

interface HttpDatasourceConfig {
	public function get_endpoint(): string;
	public function get_image_url(): string|null;
	public function get_request_headers(): array;
}
