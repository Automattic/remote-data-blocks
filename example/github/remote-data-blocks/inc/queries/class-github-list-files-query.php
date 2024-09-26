<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\GitHub;

use RemoteDataBlocks\Config\Datasource\HttpDatasource;
use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class GitHubListFilesQuery extends HttpQueryContext {
	public function __construct( private HttpDatasource $datasource, private string $file_extension ) {
		parent::__construct( $datasource );
	}

	public function get_input_schema(): array {
		return [
			'file_extension' => [
				'name' => 'File Extension',
				'type' => 'string',
			],
		];
	}

	public function get_output_schema(): array {
		return [
			'root_path' => sprintf( '$.tree[?(@.path =~ /\\.%s$/)]', ltrim( $this->file_extension, '.' ) ),
			'is_collection' => true,
			'mappings' => [
				'file_path' => [
					'name' => 'File Path',
					'path' => '$.path',
					'type' => 'string',
				],
				'sha' => [
					'name' => 'SHA',
					'path' => '$.sha',
					'type' => 'string',
				],
				'size' => [
					'name' => 'Size',
					'path' => '$.size',
					'type' => 'number',
				],
				'url' => [
					'name' => 'URL',
					'path' => '$.url',
					'type' => 'string',
				],
			],
		];
	}

	public function get_query_name(): string {
		return 'List files';
	}
}
