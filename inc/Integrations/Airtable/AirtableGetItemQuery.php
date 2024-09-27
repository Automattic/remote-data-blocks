<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Airtable;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class AirtableGetItemQuery extends HttpQueryContext {
	public function get_input_schema(): array {
		return [
			'record_id' => [
				'name'      => 'Record ID',
				'overrides' => [
					[
						'target' => 'utm_content',
						'type'   => 'query_var',
					],
				],
				'type'      => 'id',
			],
		];
	}

	/**
	 * Airtable API endpoint for fetching a single item.
	 */
	public function get_endpoint( array $input_variables ): string {
		$data_source_config = $this->get_datasource()->to_array();
		return $this->get_datasource()->get_endpoint() . '/' . $data_source_config['tables'][0]['id'] . '/' . $input_variables['record_id'];
	}

	public function get_query_name(): string {
		return $this->config['query_name'] ?? 'Get item';
	}
}
