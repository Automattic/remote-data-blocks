<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\SalesforceB2C\Queries;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Integrations\SalesforceB2C\Auth\SalesforceB2CAuth;

class SalesforceB2CSearchProductsQuery extends HttpQueryContext {
	public function get_input_schema(): array {
		return [
			'search_terms' => [
				'type' => 'string',
			],
		];
	}

	public function get_output_schema(): array {
		return [
			'root_path' => '$.hits[*]',
			'is_collection' => true,
			'mappings' => [
				'product_id' => [
					'name' => 'Product ID',
					'path' => '$.productId',
					'type' => 'id',
				],
				'name' => [
					'name' => 'Product name',
					'path' => '$.productName',
					'type' => 'string',
				],
				'price' => [
					'name' => 'Item price',
					'path' => '$.price',
					'type' => 'price',
				],
				'image_url' => [
					'name' => 'Item image URL',
					'path' => '$.image.link',
					'type' => 'image_url',
				],
			],
		];
	}

	public function get_endpoint( array $input_variables ): string {
		$data_source_endpoint = $this->get_data_source()->get_endpoint();
		$data_source_config = $this->get_data_source()->to_array();

		return sprintf(
			'%s/search/shopper-search/v1/organizations/%s/product-search?siteId=RefArchGlobal&q=%s',
			$data_source_endpoint,
			$data_source_config['organization_id'],
			urlencode( $input_variables['search_terms'] )
		);
	}

	public function get_request_headers( array $input_variables ): array {
		$data_source_config = $this->get_data_source()->to_array();
		$data_source_endpoint = $this->get_data_source()->get_endpoint();

		$access_token = SalesforceB2CAuth::generate_token(
			$data_source_endpoint,
			$data_source_config['organization_id'],
			$data_source_config['client_id'],
			$data_source_config['client_secret']
		);

		$headers = [
			'Content-Type' => 'application/json',
		];

		if ( is_wp_error( $access_token ) ) {
			return $headers;
		}

		$headers['Authorization'] = sprintf( 'Bearer %s', $access_token );
		return $headers;
	}

	public function get_query_name(): string {
		return 'Search products';
	}
}
