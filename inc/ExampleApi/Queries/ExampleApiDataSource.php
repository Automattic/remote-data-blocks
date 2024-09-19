<?php

namespace RemoteDataBlocks\ExampleApi\Queries;

use RemoteDataBlocks\Config\Datasource\DatasourceInterface;

/**
 * This is a placeholder datasource used only to represent the data source in the
 * settings UI. The actual data loading is implemented by ExampleApiQueryRunner.
 */
class ExampleApiDataSource implements DatasourceInterface {
	/**
	 * @inheritDoc
	 */
	public function get_display_name(): string {
		return 'Example API';
	}

	/**
	 * @inheritDoc
	 */
	public function get_image_url(): null {
		return null;
	}
}
