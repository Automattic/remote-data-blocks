<?php

namespace RemoteDataBlocks\Integrations\GitHub;

use RemoteDataBlocks\Config\Datasource\HttpDatasource;

class GitHubDatasource extends HttpDatasource {
	protected const SERVICE_NAME           = REMOTE_DATA_BLOCKS_GITHUB_SERVICE;
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
		return sprintf( 'GitHub: %s/%s (%s)', $this->config['repo_owner'], $this->config['repo_name'], $this->config['ref'] );
	}

	public function get_hash(): string {
		return hash( 'sha256', sprintf( '%s/%s/%s', $this->config['repo_owner'], $this->config['repo_name'], $this->config['ref'] ) );
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

	public function get_repo_owner(): string {
		return $this->config['repo_owner'];
	}

	public function get_repo_name(): string {
		return $this->config['repo_name'];
	}
	
	public function get_ref(): string {
		return $this->config['ref'];
	}
	
	public static function create( string $repo_owner, string $repo_name, string $ref ): self {
		return parent::from_array([
			'service'    => REMOTE_DATA_BLOCKS_GITHUB_SERVICE,
			'repo_owner' => $repo_owner,
			'repo_name'  => $repo_name,
			'ref'        => $ref,
			'slug'       => sanitize_title( sprintf( '%s/%s/%s', $repo_owner, $repo_name, $ref ) ),
		]);
	}
}
