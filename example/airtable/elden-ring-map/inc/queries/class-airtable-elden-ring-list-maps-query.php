<?php

namespace RemoteDataBlocks\Example\Airtable\EldenRingMap;

use RemoteDataBlocks\Config\HttpQueryContext;

class AirtableEldenRingListMapsQuery extends HttpQueryContext {
	public array $input_variables = [
		'search' => [
			'type' => 'string',
		],
	];

	public array $output_variables = [
		'root_path'     => '$.records[*]',
		'is_collection' => true,
		'mappings'      => [
			'id'       => [
				'name' => 'Map ID',
				'path' => '$.id',
				'type' => 'id',
			],
			'map_name' => [
				'name' => 'Name',
				'path' => '$.fields.Name',
				'type' => 'string',
			],
		],
	];

	public function get_endpoint( $input_variables ): string {
		return $this->get_datasource()->get_endpoint() . '/' . AirtableEldenRingMapDatasource::MAPS_TABLE;
	}

	public function get_query_name(): string {
		return 'List maps';
	}
}
