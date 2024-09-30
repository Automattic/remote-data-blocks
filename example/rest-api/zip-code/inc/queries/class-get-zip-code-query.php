<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ZipCode;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class GetZipCodeQuery extends HttpQueryContext {
	public function get_input_schema(): array {
		return [
			'zip_code' => [
				'name' => 'Zip Code',
				'type' => 'string',
			],
		];
	}

	public function get_output_schema(): array {
		return [
			'is_collection' => false,
			'mappings'      => [
				'zip_code' => [
					'name' => 'Zip Code',
					'path' => '$["post code"]',
					'type' => 'string',
				],
				'city'     => [
					'name' => 'City',
					'path' => '$.places[0]["place name"]',
					'type' => 'string',
				],
				'state'    => [
					'name' => 'State',
					'path' => '$.places[0].state',
					'type' => 'string',
				],
			],
		];
	}

	public function get_endpoint( $input_variables ): string {
		return $this->get_data_source()->get_endpoint() . $input_variables['zip_code'];
	}
}
