<?php

declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Airtable\Events;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class AirtableListEventsQuery extends HttpQueryContext {
	public array $input_variables = [];

	public array $output_variables = [
		'root_path'     => '$.records[*]',
		'is_collection' => true,
		'mappings'      => [
			'record_id' => [
				'name' => 'Record ID',
				'path' => '$.id',
				'type' => 'id',
			],
			'title'     => [
				'name' => 'Title',
				'path' => '$.fields.Activity',
				'type' => 'string',
			],
			'location'  => [
				'name' => 'Location',
				'path' => '$.fields.Location',
				'type' => 'string',
			],
			'type'      => [
				'name' => 'Type',
				'path' => '$.fields.Type',
				'type' => 'string',
			],
		],
	];

	public function get_query_name(): string {
		return 'List events';
	}

	public function get_endpoint( array $input_variables ): string {
		return $this->get_datasource()->get_endpoint() . '/tblyGtuxblLtmoqMI';
	}
}
