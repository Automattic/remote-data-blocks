<?php

namespace RemoteDataBlocks\Example\GitHub;

use RemoteDataBlocks\Config\QueryContext;

class GitHubGetFileAsRawQuery extends QueryContext {
	public array $input_variables = [
		'file_path' => [
			'name' => 'File Path',
			'type' => 'string',
		],
		'sha'       => [
			'name' => 'SHA',
			'type' => 'string',
		],
		'size'      => [
			'name' => 'Size',
			'type' => 'number',
		],
		'url'       => [
			'name' => 'URL',
			'type' => 'string',
		],
	];

	public array $output_variables = [
		'is_collection' => false,
		'mappings'      => [
			'file_content' => [
				'name' => 'File Content',
				'path' => '$.content',
				'type' => 'base64',
			],
			'file_path'    => [
				'name' => 'File Path',
				'path' => '$.path',
				'type' => 'string',
			],
			'sha'          => [
				'name' => 'SHA',
				'path' => '$.sha',
				'type' => 'string',
			],
			'size'         => [
				'name' => 'Size',
				'path' => '$.size',
				'type' => 'number',
			],
			'url'          => [
				'name' => 'URL',
				'path' => '$.url',
				'type' => 'string',
			],
		],
	];

	public function get_endpoint( array $input_variables ): string {
		return $input_variables['url'];
	}
}
