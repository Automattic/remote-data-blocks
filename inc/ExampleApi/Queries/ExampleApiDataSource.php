<?php declare(strict_types = 1);

namespace RemoteDataBlocks\ExampleApi\Queries;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use WP_Error;

/**
 * This is a placeholder DataSource used only to represent the data source in the
 * settings UI. The actual data loading is implemented by ExampleApiQueryRunner.
 */
class ExampleApiDataSource extends HttpDataSource {
	public function get_display_name(): string {
		return 'Example API';
	}

	public function get_image_url(): ?string {
		return null;
	}

	public function get_endpoint(): string {
		return '';
	}

	public function get_request_headers(): array|WP_Error {
		return [];
	}
}
