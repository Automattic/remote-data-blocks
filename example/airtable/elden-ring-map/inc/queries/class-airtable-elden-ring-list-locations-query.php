<?php

namespace RemoteDataBlocks\Example\Airtable\EldenRingMap;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class AirtableEldenRingListLocationsQuery extends HttpQueryContext {
	public function define_input_variables(): array {
		return [
			'map_name' => [
				'type' => 'string',
			],
		];
	}

	public function define_output_variables(): array {
		return [
			'root_path'     => '$.records[*]',
			'is_collection' => true,
			'mappings'      => [
				'id'    => [
					'name' => 'Location ID',
					'path' => '$.id',
					'type' => 'id',
				],
				'title' => [
					'name' => 'Name',
					'path' => '$.fields.Name',
					'type' => 'string',
				],
				'x'     => [
					'name' => 'x',
					'path' => '$.fields.x',
					'type' => 'string',
				],
				'y'     => [
					'name' => 'y',
					'path' => '$.fields.y',
					'type' => 'string',
				],
			],
		];
	}

	public function get_endpoint( array $input_variables ): string {
		return $this->get_datasource()->get_endpoint() . '/tblc82R9msH4Yh6ZX?filterByFormula=FIND%28%27' . $input_variables['map_name'] . '%27%2C%20%7BMap%7D%29%3E0';
	}
}
