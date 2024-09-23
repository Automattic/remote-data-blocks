<?php

namespace RemoteDataBlocks\Example\GitHub;

use RemoteDataBlocks\Config\Datasource\HttpDatasource;

class GitHubDatasource extends HttpDatasource {
	protected const SERVICE_SCHEMA_VERSION = 1;
	
	protected const SERVICE_SCHEMA = [
		'type'       => 'object',
		'properties' => [
			'service'                => [
				'type'  => 'string',
				'const' => REMOTE_DATA_BLOCKS_GITHUB_SERVICE,
			],
			'service_schema_version' => [
				'type'  => 'integer',
				'const' => self::SERVICE_SCHEMA_VERSION,
			],
			'repo_owner'             => [ 'type' => 'string' ],
			'repo_name'              => [ 'type' => 'string' ],
			'ref'                    => [ 'type' => 'string' ],
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

	public static function create( string $repo_owner, string $repo_name, string $ref ): self {
		return parent::from_array([
			'service'                => REMOTE_DATA_BLOCKS_GITHUB_SERVICE,
			'service_schema_version' => self::SERVICE_SCHEMA_VERSION,
			'repo_owner'             => $repo_owner,
			'repo_name'              => $repo_name,
			'ref'                    => $ref,
		]);
	}
}
