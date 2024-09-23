<?php

/**
 * HttpQueryContextInterface interface
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */

namespace RemoteDataBlocks\Config\QueryContext;

use RemoteDataBlocks\Config\QueryRunner\QueryRunnerInterface;

interface QueryContextInterface {
	public function define_input_variables(): array;
	public function define_output_variables(): array;
	public function get_image_url(): string|null;
	public function get_metadata( array $response_metadata, array $query_results ): array;
	public function get_query_name(): string;
	public function get_query_runner(): QueryRunnerInterface;
	public function is_response_data_collection(): bool;
	public function process_response( string $raw_response_string, array $input_variables ): string|array|object|null;
}
