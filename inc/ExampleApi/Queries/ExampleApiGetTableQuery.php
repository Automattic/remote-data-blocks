<?php declare(strict_types = 1);

namespace RemoteDataBlocks\ExampleApi\Queries;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use WP_Error;

// TODO delete
class ExampleApiGetTableQuery extends HttpQueryContext {
	public function execute( array $input_variables ): array|WP_Error {
		$query_runner = new ExampleApiQueryRunner( $this );
		return $query_runner->execute( $input_variables );
	}

	public function get_output_schema(): array {
		return [
			'root_path' => '$.records[*]',
			'is_collection' => true,
			'mappings' => [
				'record_id' => [
					'name' => 'Record ID',
					'path' => '$.id',
					'type' => 'id',
				],
				'title' => [
					'name' => 'Title',
					'path' => '$.fields.Activity',
					'type' => 'string',
				],
				'location' => [
					'name' => 'Location',
					'path' => '$.fields.Location',
					'type' => 'string',
				],
				'type' => [
					'name' => 'Type',
					'path' => '$.fields.Type',
					'type' => 'string',
				],
			],
		];
	}

	public function get_query_name(): string {
		return 'List events';
	}
}
