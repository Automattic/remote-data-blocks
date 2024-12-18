<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\QueryContext;

use WP_Error;

/**
 * HttpQueryContextInterface interface
 *
 */
interface HttpQueryContextInterface {
	public function get_cache_ttl( array $input_variables ): null|int;
	public function get_endpoint( array $input_variables ): string;
	public function get_request_method(): string;
	public function get_request_headers( array $input_variables ): array|WP_Error;
	public function get_request_body( array $input_variables ): array|null;
}
