<?php declare(strict_types = 1);

namespace RemoteDataBlocks\ExampleApi\Queries;

use RemoteDataBlocks\Config\Datasource\HttpDatasource;

/**
 * This is a placeholder datasource used only to represent the data source in the
 * settings UI. The actual data loading is implemented by ExampleApiQueryRunner.
 */
class ExampleApiDataSource extends HttpDatasource {
	public function get_display_name(): string {
		return 'Example API';
	}

	public function get_image_url(): ?string {
		return null;
	}

	public function get_endpoint(): string {
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public function get_request_headers(): array {
		return [];
	}
}
