<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\Query;

use RemoteDataBlocks\Config\DataSource\HttpDataSourceInterface;

use WP_Error;

/**
 * HttpQueryInterface interface
 *
 */
interface HttpQueryInterface extends QueryInterface {
	public function get_data_source(): HttpDataSourceInterface;
	public function get_cache_ttl( array $input_variables ): null|int;

	/**
	 * Get the request header names whose values should be included in cache keys.
	 *
	 * @return array<string> Request header names included in cache keys.
	 */
	public function get_cache_key_request_headers(): array;

	public function get_endpoint( array $input_variables ): string;
	public function get_request_method(): string;
	public function get_request_headers( array $input_variables ): array|WP_Error;
	public function get_request_body( array $input_variables ): array|null;
	public function preprocess_response( mixed $response_data, array $request_details ): mixed;
}
