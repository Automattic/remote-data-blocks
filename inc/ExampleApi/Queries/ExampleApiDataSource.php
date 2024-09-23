<?php

namespace RemoteDataBlocks\ExampleApi\Queries;

use RemoteDataBlocks\Config\Datasource\CodedHttpDatasource;

/**
 * This is a placeholder datasource used only to represent the data source in the
 * settings UI. The actual data loading is implemented by ExampleApiQueryRunner.
 */
class ExampleApiDataSource extends CodedHttpDatasource {
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

	public function get_object_representations(): array {
		return [
			'example-api' => (object) [
				'slug'    => 'example-api',
				'service' => 'example-api',
			],
		];
	}

	public function get_endpoint(): string {
		return '';
	}

	public function get_request_headers(): array {
		return [];
	}
}
