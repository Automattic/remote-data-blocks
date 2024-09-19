<?php

namespace RemoteDataBlocks\ExampleApi\Queries;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Config\QueryRunner\QueryRunnerInterface;

class ExampleApiGetRecordQuery extends HttpQueryContext {
	public array $input_variables = [
		'record_id' => [
			'name'      => 'Record ID',
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
				'name' => 'Record ID',
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

	public function __construct() {}

	public function get_image_url(): null {
		return null;
	}

	public function get_query_name(): string {
		return 'Get event';
	}

	public function get_query_runner(): QueryRunnerInterface {
		return new ExampleApiQueryRunner( $this );
	}
}