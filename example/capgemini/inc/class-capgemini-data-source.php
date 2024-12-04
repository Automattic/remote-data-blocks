<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Capgemini;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;

class CapgeminiDataSource extends HttpDataSource {
    public function get_display_name(): string {
        return 'Capgemini';
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
