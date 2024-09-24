<?php

declare(strict_types = 1);

/**
 * ExampleApiQueryRunner class
 *
 * @package remote-data-blocks
 */

namespace RemoteDataBlocks\ExampleApi\Queries;

use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use RemoteDataBlocks\ExampleApi\Data\ExampleApiData;
use WP_Error;

defined( 'ABSPATH' ) || exit();

/**
 * Execute the query by making an internal REST API request. This allows the
 * example API to work when running locally (inside a container). Otherwise,
 * there would be a mismatch between the public address (e.g., localhost:888) and
 * what is reachable inside a container.
 *
 * A nice side effect is that we avoid using Guzzle/cURL for this example, which
 * makes it runnable in environments like WP Now.
 */
class ExampleApiQueryRunner extends QueryRunner {
	protected function get_raw_response_data( array $input_variables ): array|WP_Error {
		if ( isset( $input_variables['record_id'] ) ) {
			return [
				'metadata'      => [],
				'response_data' => ExampleApiData::get_item( $input_variables['record_id'] ),
			];
		}

		return [
			'metadata'      => [],
			'response_data' => ExampleApiData::get_items(),
		];
	}
}
