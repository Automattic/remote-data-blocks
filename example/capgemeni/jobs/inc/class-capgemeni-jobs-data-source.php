<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\CapGemeni\Jobs;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;

class CapGemeniJobsDataSource extends HttpDataSource {
	public function get_display_name(): string {
		return 'Cap Gemeni Jobs';
	}

	public function get_endpoint(): string {
		return 'https://www.capgemini.com/gb-en/wp-json/macs/v1';
	}

	public function get_request_headers(): array {
		return [
			'Content-Type' => 'application/json',
		];
	}
}
