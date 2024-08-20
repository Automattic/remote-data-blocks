<?php

namespace RemoteDataBlocks\Example\Airtable\Events;

use RemoteDataBlocks\Config\QueryContext;

class AirtableListEventsQuery extends QueryContext {
	public array $input_variables = [
		'search' => [
			'type' => 'string',
		],
	];

	public array $output_variables = [
		'root_path'     => '$.records[*]',
		'is_collection' => true,
		'mappings'      => [
			'event_id' => [
				'name' => 'Event ID',
				'path' => '$.id',
				'type' => 'id',
			],
			'title'    => [
				'name' => 'Title',
				'path' => '$.fields.Activity',
				'type' => 'string',
			],
			'location' => [
				'name' => 'Location',
				'path' => '$.fields.Location',
				'type' => 'string',
			],
			'type'     => [
				'name' => 'Type',
				'path' => '$.fields.Type',
				'type' => 'string',
			],
		],
	];
}
