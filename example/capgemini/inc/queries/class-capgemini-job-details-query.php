<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Capgemini;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class CapgeminiJobDetailsQuery extends HttpQueryContext {
    public function get_input_schema(): array {
        return [
            'id' => [
                'name' => 'Job ID',
                'type' => 'string',
                'overrides' => [
                    [
                        'target' => 'id',
                        'type' => 'url',
                    ],
                ],
            ],
        ];
    }

    public function get_output_schema(): array {
        return [
            'is_collection' => false,
            'mappings' => [
                'id' => [
                    'name' => 'Job ID',
                    'path' => '$.data.id',
                    'type' => 'id',
                ],
                'title' => [
                    'name' => 'Title',
                    'path' => '$.data.title',
                    'type' => 'string',
                ],
                'location' => [
                    'name' => 'Location',
                    'path' => '$.data.location',
                    'type' => 'string',
                ],
                'description' => [
                    'name' => 'Description',
                    'path' => '$.data.description',
                    'type' => 'html',
                ],
                'contract_type' => [
                    'name' => 'Contract Type',
                    'path' => '$.data.contract_type',
                    'type' => 'string',
                ],
                'experience_level' => [
                    'name' => 'Experience Level',
                    'path' => '$.data.experience_level',
                    'type' => 'string',
                ],
                'department' => [
                    'name' => 'Department',
                    'path' => '$.data.department',
                    'type' => 'string',
                ],
                'apply_url' => [
                    'name' => 'Apply URL',
                    'path' => '$.data.apply_job_url',
                    'type' => 'url',
                ],
            ],
        ];
    }

    public function get_endpoint( array $input_variables ): string {
        return $this->get_data_source()->get_endpoint() . '/job-details/' . $input_variables['id'];
    }
} 