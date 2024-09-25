<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\QueryContext;

use RemoteDataBlocks\Config\QueryRunner\QueryRunnerInterface;

/**
 * QueryContextInterface interface
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */
interface QueryContextInterface {
	public function get_image_url(): string|null;
	public function get_input_schema(): array;
	public function get_output_schema(): array;
	public function get_query_name(): string;
	public function get_query_runner(): QueryRunnerInterface;
	public function is_response_data_collection(): bool;
	public function process_response( string $raw_response_data, array $input_variables ): string|array|object|null;
}
