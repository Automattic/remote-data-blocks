<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Airtable\EldenRingMap;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class AirtableEldenRingListLocationsQuery extends HttpQueryContext {
	public function get_input_schema(): array {
		return [
			'map_name' => [
				'type' => 'string',
			],
		];
	}

	public function get_output_schema(): array {
		return [
			'root_path'     => '$.records[*]',
			'is_collection' => true,
			'mappings'      => [
				'id'       => [
					'name' => 'Location ID',
					'path' => '$.id',
					'type' => 'id',
				],
				'map_name' => [
					'name' => 'Name',
					'path' => '$.fields.Name',
					'type' => 'string',
				],
				'title'    => [
					'name' => 'Name',
					'path' => '$.fields.Name',
					'type' => 'string',
				],
				'x'        => [
					'name' => 'x',
					'path' => '$.fields.x',
					'type' => 'string',
				],
				'y'        => [
					'name' => 'y',
					'path' => '$.fields.y',
					'type' => 'string',
				],
			],
		];
	}

	public function get_endpoint( array $input_variables ): string {
		return $this->get_data_source()->get_endpoint() . '/tblc82R9msH4Yh6ZX?filterByFormula=FIND%28%27' . $input_variables['map_name'] . '%27%2C%20%7BMap%7D%29%3E0';
	}
}
