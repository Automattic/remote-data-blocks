<?php

namespace RemoteDataBlocks\Integrations\Airtable;

use RemoteDataBlocks\Config\Datasource\DatasourceInterface;
use RemoteDataBlocks\Config\Datasource\HttpDatasource;
use RemoteDataBlocks\Config\Datasource\HttpDatasourceInterface;

class AirtableDatasource extends HttpDatasource implements HttpDatasourceInterface {
	private const SERVICE_SCHEMA = [
		'access_token' => [
			'path'     => '$.access_token',
			'required' => true,
			'type'     => 'string',
		],
		'base'         => [
			'path'     => '$.base',
			'required' => true,
			'type'     => 'array',
			'items'    => [
				'type'       => 'object',
				'properties' => [
					'id'   => [
						'type'     => 'string',
						'required' => true,
					],
					'name' => [
						'type'     => 'string',
						'required' => true,
					],
				],
			],
		],
		'tables'       => [
			'path'     => '$.tables',
			'required' => true,
			'type'     => 'array',
			'items'    => [
				'type'       => 'object',
				'properties' => [
					'id'   => [
						'type'     => 'string',
						'required' => true,
					],
					'name' => [
						'type'     => 'string',
						'required' => true,
					],
				],
			],
		],
	];

	private $access_token;
	private $base;
	private $tables;

	public function get_access_token(): string {
		return $this->access_token;
	}

	public function get_base(): string {
		return $this->base;
	}

	public function get_display_name(): string {
		$suffix = count( $this->tables ) > 1 ? ' (' . implode( ', ', array_keys( $this->tables ) ) . ')' : '';
		return trim( sprintf( 'Airtable: %s %s', $this->get_base(), $suffix ) );
	}

	public function get_table( string $variation = '' ): string {
		return $this->tables[ $variation ] ?? '';
	}

	public function get_endpoint( string $variation = '' ): string {
		$url   = 'https://api.airtable.com/v0/' . $this->get_base();
		$table = $this->get_table( $variation );
		if ( $table ) {
			$url .= '/' . $table;
		}
		return $url;
	}

	public function get_request_headers(): array {
		return [
			'Authorization' => sprintf( 'Bearer %s', $this->get_access_token() ),
			'Content-Type'  => 'application/json',
		];
	}

	public static function get_config_schema(): array {
		return array_merge( DatasourceInterface::BASE_SCHEMA, self::SERVICE_SCHEMA );
	}
}
