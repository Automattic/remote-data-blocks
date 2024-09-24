<?php

declare(strict_types = 1);

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

	public static function create( string $access_token, string $base_id, string $display_name ): self {
		return parent::from_array([
			'service'      => REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE,
			'access_token' => $access_token,
			'base'         => [ 'id' => $base_id ],
			'display_name' => $display_name,
			'slug'         => sanitize_title( $display_name ),
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
			'uuid'    => $this->config['uuid'] ?? null,
		];
	}
}
