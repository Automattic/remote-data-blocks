<?php

declare(strict_types = 1);

namespace RemoteDataBlocks\Config\QueryContext;

/**
 * HttpQueryContextInterface interface
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */
interface HttpQueryContextInterface {
	public function get_endpoint( array $input_variables ): string;
	public function get_request_method(): string;
	public function get_request_headers( array $input_variables ): array;
	public function get_request_body( array $input_variables ): array|null;
}
