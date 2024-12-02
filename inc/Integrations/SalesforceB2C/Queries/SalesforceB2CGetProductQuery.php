<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\SalesforceB2C\Queries;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Integrations\SalesforceB2C\Auth\SalesforceB2CAuth;

class SalesforceB2CGetProductQuery extends HttpQueryContext {
	public function get_input_schema(): array {
		return [
			'product_id' => [
				'name' => 'Product ID',
				'overrides' => [
					[
						'target' => 'utm_content',
						'type' => 'query_var',
					],
				],
				'type' => 'id',
			],
		];
	}

	public function get_output_schema(): array {
		return [
			'is_collection' => false,
			'mappings' => [
				'id' => [
					'name' => 'Product ID',
					'path' => '$.id',
					'type' => 'id',
				],
				'name' => [
					'name' => 'Namef',
					'path' => '$.name',
					'type' => 'string',
				],
				'longDescription' => [
					'name' => 'Long Description',
					'path' => '$.longDescription',
					'type' => 'string',
				],
				'price' => [
					'name' => 'Price',
					'path' => '$.price',
					'type' => 'string',
				],
				'image_url' => [
					'name' => 'Image URL',
					'path' => '$.imageGroups[0].images[0].link',
					'type' => 'image_url',
				],
				'image_alt_text' => [
					'name' => 'Image Alt Text',
					'path' => '$.imageGroups[0].images[0].alt',
					'type' => 'image_alt',
				],
			],
		];
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
			// alecg todo: Surface error in get_request_headers() parent call
			return $headers;
		}

		$headers['Authorization'] = sprintf( 'Bearer %s', $access_token );
		return $headers;
	}

	public function get_endpoint( array $input_variables ): string {
		$data_source_endpoint = $this->get_data_source()->get_endpoint();
		$data_source_config = $this->get_data_source()->to_array();

		return sprintf( '%s/product/shopper-products/v1/organizations/%s/products/%s?siteId=RefArchGlobal', $data_source_endpoint, $data_source_config['organization_id'], $input_variables['product_id'] );
	}

	public function get_query_name(): string {
		return $this->config['query_name'] ?? 'Get item';
	}
}
