<?php

/**
 * HttpQueryContextInterface interface
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */

namespace RemoteDataBlocks\Config;

use Psr\Http\Message\ResponseInterface;

interface QueryContextInterface {
	public function get_image_url(): string|null;
	public function get_metadata( ResponseInterface $response, array $query_results ): array;
	public function get_query_name(): string;
	public function get_query_runner(): QueryRunnerInterface;
	public function is_response_data_collection(): bool;
}
