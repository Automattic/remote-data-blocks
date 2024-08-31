<?php

namespace RemoteDataBlocks\Example\GitHub;

use RemoteDataBlocks\Config\QueryContext;

class GitHubListFilesQuery extends QueryContext {
	public array $input_variables = [
		'search' => [
			'type' => 'string',
		],
	];

	public array $output_variables = [
		'root_path'     => '$.tree[?(@.path =~ /\\.md$/)]',
		'is_collection' => true,
		'mappings'      => [
			'file_path' => [
				'name' => 'File Path',
				'path' => '$.path',
				'type' => 'string',
			],
			'sha'       => [
				'name' => 'SHA',
				'path' => '$.sha',
				'type' => 'string',
			],
			'size'      => [
				'name' => 'Size',
				'path' => '$.size',
				'type' => 'number',
			],
			'url'       => [
				'name' => 'URL',
				'path' => '$.url',
				'type' => 'string',
			],
		],
	];

	public function get_query_name(): string {
		return 'List files';
	}
}
