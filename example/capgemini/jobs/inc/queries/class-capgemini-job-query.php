<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Capgemini\Jobs;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class CapgeminiJobQuery extends HttpQueryContext {
	public function get_input_schema(): array {
		return [
			'id' => [
				'type' => 'id',
			],
		];
	}

	public function get_output_schema(): array {
		return [
			'is_collection' => false,
			'mappings' => [
				'id' => [
					'name' => 'Job ID',
					'path' => '$._id',
					'type' => 'id',
				],
				'title' => [
					'name' => 'Title',
					'path' => '$.title',
					'type' => 'string',
				],
				'description' => [
					'name' => 'Description',
					'path' => '$.description',
					'type' => 'string',
				],
				'location' => [
					'name' => 'Location',
					'path' => '$.location',
					'type' => 'string',
				],
				'apply_job_url' => [
					'name' => 'Apply URL',
					'path' => '$.apply_job_url',
					'type' => 'button_url',
				],
				'contract_type' => [
					'name' => 'Contract Type',
					'path' => '$.contract_type',
					'type' => 'string',
				],
				'custom_criteria_value' => [
					'name' => 'Custom Criteria',
					'path' => '$.custom_criteria_value',
					'type' => 'string',
				],
				'department' => [
					'name' => 'Department',
					'path' => '$.department',
					'type' => 'string',
				],
				'education_level' => [
					'name' => 'Education Level',
					'path' => '$.education_level',
					'type' => 'string',
				],
				'experience_level' => [
					'name' => 'Experience Level',
					'path' => '$.experience_level',
					'type' => 'string',
				],
				'sbu' => [
					'name' => 'SBU',
					'path' => '$.sbu',
					'type' => 'string',
				],
				'site' => [
					'name' => 'Site',
					'path' => '$.site',
					'type' => 'string',
				],
				'professional_communities' => [
					'name' => 'Professional Communities',
					'path' => '$.professional_communities',
					'type' => 'string',
				],
				'country_code' => [
					'name' => 'Country Code',
					'path' => '$.country_code',
					'type' => 'string',
				],
				'country_name' => [
					'name' => 'Country Name',
					'path' => '$.country_name',
					'type' => 'string',
				],
				'brand' => [
					'name' => 'Brand',
					'path' => '$.brand',
					'type' => 'string',
				],
				'status' => [
					'name' => 'Status',
					'path' => '$.status',
					'type' => 'string',
				],
				'source' => [
					'name' => 'Source',
					'path' => '$.source',
					'type' => 'string',
				],
				'source_ref' => [
					'name' => 'Source Reference',
					'path' => '$.source_ref',
					'type' => 'string',
				],
			],
		];
	}

	public function get_endpoint( $input_variables ): string {
		return $this->get_data_source()->get_endpoint() . '/job-details/' . $input_variables['id'];
	}
}