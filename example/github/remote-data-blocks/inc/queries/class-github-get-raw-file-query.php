<?php

namespace RemoteDataBlocks\Example\GitHub;

use RemoteDataBlocks\Config\QueryContext;

class GitHubGetRawFileQuery extends QueryContext {
	public array $input_variables = [
		'file_path' => [
			'name' => 'File URL',
			'type' => 'string',
		],
	];

	public array $output_variables = [
		'is_collection' => false,
		'mappings'      => [
			'content' => [
				'name' => 'Content',
				'path' => '$',
				'type' => 'string',
			],
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

	public function get_endpoint( $input_variables ): string {
		$datasource = $this->get_datasource();
		return sprintf(
			'https://raw.githubusercontent.com/%s/%s/%s/%s',
			$datasource->repo_owner,
			$datasource->repo_name,
			$datasource->branch,
			$input_variables['file_path']
		);
	}
}