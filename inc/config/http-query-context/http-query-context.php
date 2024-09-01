<?php

/**
 * HttpQueryContext interface
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */

namespace RemoteDataBlocks\Config;

use Psr\Http\Message\ResponseInterface;

interface HttpQueryContext {
	public function get_endpoint( array $input_variables ): string;
	public function get_image_url(): string|null;
	public function get_metadata( ResponseInterface $response, array $query_results ): array;
	public function get_request_method(): string;
	public function get_request_headers( array $input_variables ): array;
	public function get_request_body( array $input_variables ): array|null;
	public function get_query_name(): string;
	public function get_query_runner(): QueryRunnerInterface;
	public function get_results( string $response_data, array $input_variables ): array;
	public function is_collection(): bool;
}