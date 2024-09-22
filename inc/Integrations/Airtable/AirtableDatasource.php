<?php

namespace RemoteDataBlocks\Integrations\Airtable;

use RemoteDataBlocks\Config\Datasource\DatasourceInterface;
use RemoteDataBlocks\Config\Datasource\HttpDatasource;
use RemoteDataBlocks\Config\Datasource\HttpDatasourceInterface;

class AirtableDatasource extends HttpDatasource implements HttpDatasourceInterface {
	private const SERVICE_SCHEMA = [
		'type'       => 'object',
		'properties' => [
			'access_token' => [ 'type' => 'string' ],
			'base'         => [
				'type'       => 'object',
				'properties' => [
					'id'   => [ 'type' => 'string' ],
					'name' => [ 'type' => 'string' ],
				],
			],
			'tables'       => [
				'type'  => 'object',
				'items' => [
					'id'   => [ 'type' => 'string' ],
					'name' => [ 'type' => 'string' ],
				],
			],
		],
	];

	public function get_access_token(): string {
		return $this->config['access_token'];
	}

	public function get_base(): string {
		return $this->config['base']['id'];
	}

	public function get_display_name(): string {
		$suffix = count( $this->config['tables'] ) > 1 ? ' (' . implode( ', ', array_keys( $this->config['tables'] ) ) . ')' : '';
		return trim( sprintf( 'Airtable: %s %s', $this->get_base(), $suffix ) );
	}

	public function get_table( string $variation = '' ): string {
		return $this->config['tables'][ $variation ] ?? '';
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
