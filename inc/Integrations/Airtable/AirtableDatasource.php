<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Airtable;

use RemoteDataBlocks\Config\Datasource\HttpDatasource;

class AirtableDatasource extends HttpDatasource {
	protected const SERVICE_SCHEMA_VERSION = 1;

	protected const SERVICE_SCHEMA = [
		'type'       => 'object',
		'properties' => [
			'service'                => [
				'type'  => 'string',
				'const' => REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE,
			],
			'service_schema_version' => [
				'type'  => 'integer',
				'const' => self::SERVICE_SCHEMA_VERSION,
			],
			'access_token'           => [ 'type' => 'string' ],
			'base'                   => [
				'type'       => 'object',
				'properties' => [
					'id'   => [ 'type' => 'string' ],
					'name' => [
						'type'     => 'string',
						'required' => false,
					],
				],
			],
			'tables'                 => [
				'type'  => 'array',
				'items' => [
					'type'       => 'object',
					'properties' => [
						'id'                    => [ 'type' => 'string' ],
						'name'                  => [
							'type'     => 'string',
							'required' => false,
						],
						'output_query_mappings' => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'name' => [ 'type' => 'string' ],
								],
							],
						],
					],
				],
			],
			'display_name'           => [
				'type'     => 'string',
				'required' => false,
			],
		],
	];

	public function get_display_name(): string {
		return sprintf( 'Airtable: %s', $this->config['display_name'] ?? $this->config['base']['name'] );
	}

	public function get_endpoint( string $variation = '' ): string {
		return 'https://api.airtable.com/v0/' . $this->config['base']['id'];
	}

	public function get_request_headers(): array {
		return [
			'Authorization' => sprintf( 'Bearer %s', $this->config['access_token'] ),
			'Content-Type'  => 'application/json',
		];
	}

	public static function create( string $access_token, string $base_id, ?array $tables = [], ?string $display_name = null ): self {
		return parent::from_array([
			'service'      => REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE,
			'access_token' => $access_token,
			'base'         => [ 'id' => $base_id ],
			'tables'       => $tables,
			'display_name' => $display_name,
			'slug'         => $display_name ? sanitize_title( $display_name ) : sanitize_title( 'Airtable ' . $base_id ),
		]);
	}

	public function to_ui_display(): array {
		return [
			'slug'    => $this->get_slug(),
			'service' => REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE,
			'base'    => [
				'id'   => $this->config['base']['id'],
				'name' => $this->config['base']['name'] ?? null,
			],
			'tables'  => $this->config['tables'] ?? [],
			'uuid'    => $this->config['uuid'] ?? null,
		];
	}
}
