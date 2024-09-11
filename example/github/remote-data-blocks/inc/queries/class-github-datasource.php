<?php

namespace RemoteDataBlocks\Example\GitHub;

use RemoteDataBlocks\Config\HttpDatasource;

class GitHubDatasource extends HttpDatasource {
	public string $repo_owner;
	public string $repo_name;
	public string $ref;

	public function __construct( string $repo_owner, string $repo_name, string $ref = 'main' ) {
		$this->repo_owner = $repo_owner;
		$this->repo_name  = $repo_name;
		$this->ref        = $ref;
	}

	public function get_display_name(): string {
		return 'GitHub';
	}

	public function get_uid(): string {
		return hash( 'sha256', $this->repo_owner . $this->repo_name . $this->ref );
	}

	public function get_endpoint(): string {
		return sprintf(
			'https://api.github.com/repos/%s/%s/git/trees/%s?recursive=1',
			$this->repo_owner,
			$this->repo_name,
			$this->ref
		);
	}

	public function get_request_headers(): array {
		return [
			'Accept' => 'application/vnd.github+json',
		];
	}
}
