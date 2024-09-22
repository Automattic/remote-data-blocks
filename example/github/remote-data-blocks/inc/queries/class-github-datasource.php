<?php

namespace RemoteDataBlocks\Example\GitHub;

use RemoteDataBlocks\Config\Datasource\DatasourceInterface;
use RemoteDataBlocks\Config\Datasource\HttpDatasource;

class GitHubDatasource extends HttpDatasource {
	const SERVICE_SCHEMA = [
		'type'       => 'object',
		'properties' => [
			'repo_owner' => [
				'type' => 'string',
			],
			'repo_name'  => [
				'type' => 'string',
			],
			'ref'        => [
				'type' => 'string',
			],
		],
	];

	public function get_display_name(): string {
		return 'GitHub';
	}

	public function get_endpoint(): string {
		return sprintf(
			'https://api.github.com/repos/%s/%s/git/trees/%s?recursive=1',
			$this->config['repo_owner'],
			$this->config['repo_name'],
			$this->config['ref']
		);
	}

	public function get_request_headers(): array {
		return [
			'Accept' => 'application/vnd.github+json',
		];
	}

	public static function get_config_schema(): array {
		$schema               = DatasourceInterface::BASE_SCHEMA;
		$schema['properties'] = array_merge( DatasourceInterface::BASE_SCHEMA['properties'], self::SERVICE_SCHEMA['properties'] );
		return $schema;
	}
}
