<?php

namespace RemoteDataBlocks\Example\Airtable\Events;

use RemoteDataBlocks\Config\QueryContext;

class AirtableGetEventQuery extends QueryContext {
	public array $input_variables = [
		'event_id' => [
			'name'      => 'Event ID',
			'overrides' => [
				[
					'target' => 'utm_content',
					'type'   => 'query_var',
				],
			],
			'type'      => 'id',
		],
	];

	public array $output_variables = [
		'is_collection' => false,
		'mappings'      => [
			'id'       => [
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
			'notes'    => [
				'name' => 'Notes',
				'path' => '$.fields.Notes',
				'type' => 'string',
			],
			'type'     => [
				'name' => 'Type',
				'path' => '$.fields.Type',
				'type' => 'string',
			],
		],
	];

	/**
	 * Airtable API endpoint for fetching a single event.
	 */
	public function get_endpoint( $input_variables ): string {
		return $this->get_datasource()->get_endpoint() . '/' . $input_variables['event_id'];
	}

	public function get_cache_ttl( array $input_variables ): int {
		return -1;
	}
}
