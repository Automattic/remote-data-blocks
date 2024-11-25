<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\SalesforceB2C\Queries;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class SalesforceB2CListProductsQuery extends HttpQueryContext {
	public function get_endpoint( array $input_variables ): string {
		$data_source_config = $this->get_data_source()->to_array();
		return $this->get_data_source()->get_endpoint() . '/' . $data_source_config['tables'][0]['id'];
	}

	public function get_query_name(): string {
		return $this->config['query_name'] ?? 'List items';
	}
}
