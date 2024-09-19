<?php

namespace RemoteDataBlocks\ExampleApi\Queries;

use RemoteDataBlocks\Config\Datasource\HttpDatasource;

/**
 * This is a placeholder datasource used only to represent the data source in the
 * settings UI. The actual data loading is implemented by ExampleApiQueryRunner.
 */
class ExampleApiDataSource extends HttpDatasource {
	/**
	 * @inheritDoc
	 */
	public function get_display_name(): string {
		return 'Example API';
	}

	/**
	 * @inheritDoc
	 */
	public function get_endpoint(): string {
		return 'https://example.com/api/v1';
	}

	/**
	 * @inheritDoc
	 */
	public function get_request_headers(): array {
		return [];
	}
}
