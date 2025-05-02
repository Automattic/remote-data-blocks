<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\GenericHttp;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Validation\Types;

class GenericHttpDataSource extends HttpDataSource {
	protected const SERVICE_NAME = REMOTE_DATA_BLOCKS_GENERIC_HTTP_SERVICE;
	protected const SERVICE_SCHEMA_VERSION = 1;

	protected static function get_service_config_schema(): array {
		return Types::object( [
			'__version' => Types::integer(),
			'auth' => Types::nullable( Types::object( [
				'type' => Types::string(),
				'key' => Types::nullable( Types::string() ),
				'value' => Types::string(),
				'add_to' => Types::nullable( Types::string() ),
			] ) ),
			'display_name' => Types::string(),
			'endpoint' => Types::string(),
		] );
	}

	public static function get_endpoint_from_service_config( array $service_config ): string {
		$endpoint = $service_config['endpoint'];
		$auth_config = $service_config['auth'] ?? null;
		$auth_type = $auth_config['type'] ?? null;

		if ( 'api-key' === $auth_type && 'queryparams' === $auth_config['add_to'] ) {
			return add_query_arg( $auth_config['key'], $auth_config['value'], $endpoint );
		}

		return $endpoint;
	}

	public static function get_request_headers_from_service_config( array $service_config ): array {
		$auth_config = $service_config['auth'] ?? null;
		$auth_type = $auth_config['type'] ?? null;

		switch ( $auth_type ) {
			case 'bearer':
				return [ 'Authorization' => 'Bearer ' . $auth_config['value'] ];

			case 'basic':
				return [ 'Authorization' => 'Basic ' . base64_encode( $auth_config['value'] ) ];

			case 'api-key':
				if ( 'header' === $auth_config['add_to'] ) {
					return [ $auth_config['key'] => $auth_config['value'] ];
				}
		}

		return [];
	}


	protected static function map_service_config( array $service_config ): array {
		return [
			'display_name' => $service_config['display_name'],
			'endpoint' => self::get_endpoint_from_service_config( $service_config ),
			'request_headers' => self::get_request_headers_from_service_config( $service_config ),
		];
	}
}
