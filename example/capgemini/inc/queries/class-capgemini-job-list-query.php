<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Capgemini;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class CapgeminiJobListQuery extends HttpQueryContext {
    public function get_input_schema(): array {
        return [
            'search' => [
                'name' => 'Search',
                'type' => 'string',
                'required' => false,
                'overrides' => [
                    [
                        'target' => 'search',
                        'type' => 'query_var',
                    ],
                ],
            ],
        ];
    }

    public function get_output_schema(): array {
        return [
            'root_path' => '$.data[*]',
            'is_collection' => true,
            'mappings' => [
                'id' => [
                    'name' => 'Job ID',
                    'path' => '$.id',
                    'type' => 'id',
                ],
                'title' => [
                    'name' => 'Title',
                    'path' => '$.title',
                    'type' => 'string',
                ],
                'location' => [
                    'name' => 'Location',
                    'path' => '$.location',
                    'type' => 'string',
                ],
                'contract_type' => [
                    'name' => 'Contract Type',
                    'path' => '$.contract_type',
                    'type' => 'string',
                ],
                'experience_level' => [
                    'name' => 'Experience Level',
                    'path' => '$.experience_level',
                    'type' => 'string',
                ],
                'department' => [
                    'name' => 'Department',
                    'path' => '$.department',
                    'type' => 'string',
                ],
                'brand' => [
                    'name' => 'Brand',
                    'path' => '$.brand',
                    'type' => 'string',
                ],
                'description' => [
                    'name' => 'Description',
                    'path' => '$.description',
                    'type' => 'html',
                ],
                'apply_url' => [
                    'name' => 'Apply URL',
                    'path' => '$.apply_job_url',
                    'type' => 'url',
                ],
                'sbu' => [
                    'name' => 'Business Unit',
                    'path' => '$.sbu',
                    'type' => 'string',
                ],
            ],
        ];
    }

    public function get_endpoint( array $input_variables ): string {
        $endpoint = $this->get_data_source()->get_endpoint() . '/job-search';
        
        if ( ! empty( $input_variables['search'] ) ) {
            $endpoint .= '?search=' . urlencode( $input_variables['search'] );
        }
        
        return $endpoint;
    }
} 