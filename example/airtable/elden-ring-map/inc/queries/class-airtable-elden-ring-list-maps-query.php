<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Airtable\EldenRingMap;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class AirtableEldenRingListMapsQuery extends HttpQueryContext {
	public function get_input_schema(): array {
		return [
			'search' => [
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
	}

	public function get_endpoint( $input_variables ): string {
		return $this->get_data_source()->get_endpoint() . '/tblS3OYo8tZOg04CP';
	}

	public function get_query_name(): string {
		return 'List maps';
	}
}
