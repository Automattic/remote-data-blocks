<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\QueryContext;

use WP_Error;

/**
 * QueryContextInterface interface
 *
 */
interface QueryContextInterface {
	public function execute( array $input_variables ): array|WP_Error;
	public function get_image_url(): ?string;
	public function get_input_schema(): array;
	public function get_output_schema(): array;
	public function get_query_key(): string;
	public function get_query_name(): string;
}
