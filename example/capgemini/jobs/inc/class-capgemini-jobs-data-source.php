<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Capgemini\Jobs;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;

class CapgeminiJobsDataSource extends HttpDataSource {
	public static function create(): self {
		return parent::from_array( [
			'service' => 'capgemini-jobs',
			'slug' => 'capgemini-jobs',
		] );
	}

	public function get_display_name(): string {
		return 'Capgemini Jobs';
	}

	public function get_endpoint(): string {
		return 'https://cg-job-search-microservices.azurewebsites.net/api';
	}

	public function get_request_headers(): array {
		return [
			'Content-Type' => 'application/json',
		];
	}
}
