<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Airtable;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class AirtableListItemsQuery extends HttpQueryContext {
	/**
	 * Airtable API endpoint for listing items.
	 */
	public function get_endpoint( array $input_variables ): string {
		$data_source_config = $this->get_datasource()->to_array();
		return $this->get_datasource()->get_endpoint() . '/' . $data_source_config['tables'][0]['id'];
	}

	public function get_query_name(): string {
		return $this->config['query_name'] ?? 'List items';
	}
}
