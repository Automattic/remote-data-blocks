<?php

namespace RemoteDataBlocks\Example\GitHub;

use RemoteDataBlocks\Config\QueryContext;

class GitHubGetFileAsHtmlQuery extends QueryContext {
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
				'type' => 'string',
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
		return sprintf(
			'https://api.github.com/repos/%s/%s/contents/%s?ref=%s',
			$this->get_datasource()->repo_owner,
			$this->get_datasource()->repo_name,
			$input_variables['file_path'],
			$this->get_datasource()->ref
		);
	}

	public function get_request_headers( array $input_variables ): array {
		return [
			'Accept' => 'application/vnd.github.html+json',
		];
	}

	public function get_results( string $response_data, array $input_variables ): array {
		return [
			[
				'result' => [
					'file_content' => [
						'name'  => 'File Content',
						'path'  => '$.content',
						'type'  => 'string',
						'value' => $response_data,
					],
					'file_path'    => [
						'name'  => 'File Path',
						'path'  => '$.path',
						'type'  => 'string',
						'value' => $input_variables['file_path'],
					],
					'sha'          => [
						'name'  => 'SHA',
						'path'  => '$.sha',
						'type'  => 'string',
						'value' => $input_variables['sha'],
					],
					'size'         => [
						'name'  => 'Size',
						'path'  => '$.size',
						'type'  => 'number',
						'value' => $input_variables['size'],
					],
					'url'          => [
						'name'  => 'URL',
						'path'  => '$.url',
						'type'  => 'string',
						'value' => $input_variables['url'],
					],
				],
			],
		];
	}
}
