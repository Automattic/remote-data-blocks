<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Capgemini\Jobs;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class CapgeminiJobFiltersQuery extends HttpQueryContext {
	public function get_input_schema(): array {
		return [
			'search' => [
				'type' => 'string',
			],
			'country_code' => [
				'type' => 'string',
			],
		];
	}

	public function get_output_schema(): array {
		return [
			'root_path' => '$.data[*]',
			'is_collection' => true,
			'mappings' => [
				'type' => [
					'name' => 'Type',
					'path' => '$.type',
					'type' => 'string',
				],
				'items' => [
					'name' => 'Items',
					'path' => '$.item[*]',
					'type' => 'json',
				],
			],
		];
	}

	public function get_endpoint( array $input_variables ): string {
		$endpoint = $this->get_data_source()->get_endpoint() . '/job-filters';

		return add_query_arg( array_merge( $input_variables, [ 'country_code' => 'gb-en' ] ), $endpoint );
	}
}
