<?php

namespace RemoteDataBlocks\Example\GitHub;

use RemoteDataBlocks\Config\HttpDatasource;

class GitHubDatasource extends HttpDatasource {
	private string $repo_owner;
	private string $repo_name;
	private string $branch;

	public function __construct( string $repo_owner, string $repo_name, string $branch = 'trunk' ) {
		$this->repo_owner = $repo_owner;
		$this->repo_name  = $repo_name;
		$this->branch     = $branch;
	}

	public function get_endpoint(): string {
		return sprintf(
			'https://api.github.com/repos/%s/%s/git/trees/%s?recursive=1',
			$this->repo_owner,
			$this->repo_name,
			$this->branch
		);
	}

	public function get_request_headers(): array {
		return [
			'Accept' => 'application/vnd.github+json',
		];
	}
}
