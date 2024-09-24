<?php

namespace RemoteDataBlocks\Integrations\GenericHttp;

use RemoteDataBlocks\Config\Datasource\HttpDatasource;

class GenericHttpDatasource extends HttpDatasource {
	protected const SERVICE_NAME           = REMOTE_DATA_BLOCKS_GENERIC_HTTP_SERVICE;
	protected const SERVICE_SCHEMA_VERSION = 1;
	
	protected const SERVICE_SCHEMA = [
		'type'       => 'object',
		'properties' => [
			'service'                => [
				'type'  => 'string',
				'const' => REMOTE_DATA_BLOCKS_GENERIC_HTTP_SERVICE,
			],
			'service_schema_version' => [
				'type'  => 'integer',
				'const' => self::SERVICE_SCHEMA_VERSION,
			],
			'auth_type'              => [
				'type' => 'string',
				'enum' => [ 'basic', 'bearer', 'api-key' ],
			],
			'auth_value'             => [
				'type'     => 'string',
				'sanitize' => false,
			],
			'url'                    => [
				'type'     => 'string',
				'sanitize' => 'sanitize_url',
			],
		],
	];

	public function get_display_name(): string {
		return 'HTTP Connection (' . $this->config['slug'] . ')';
	}

	public function get_endpoint(): string {
		return $this->config['url'];
	}

	public function get_request_headers(): array {
		return [
			'Accept' => 'application/json',
		];
	}
	
	public static function create( string $url, string $auth, string $display_name ): self {
		return parent::from_array([
			'service' => REMOTE_DATA_BLOCKS_GENERIC_HTTP_SERVICE,
			'url'     => $url,
			'auth'    => $auth,
			'slug'    => sanitize_title( $display_name ),
		]);
	}
}
