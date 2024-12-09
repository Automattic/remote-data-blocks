<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\GenericHttp;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Validation\Types;

class GenericHttpDataSource extends HttpDataSource {
	protected const SERVICE_NAME = REMOTE_DATA_BLOCKS_GENERIC_HTTP_SERVICE;
	protected const SERVICE_SCHEMA_VERSION = 1;

	protected const SERVICE_SCHEMA = Types::object( [
		'service' => Types::const( REMOTE_DATA_BLOCKS_GENERIC_HTTP_SERVICE ),
		'service_schema_version' => Types::const( self::SERVICE_SCHEMA_VERSION ),
		'auth' => Types::object( [
			'type' => Types::enum( 'basic', 'bearer', 'api-key', 'none' ),
			'value' => Types::string( /* TODO sanitize: false? */ ),
			'key' => Types::nullable( Types::string( /* TODO sanitize: false? */ ) ),
			'add_to' => Types::nullable( Types::enum( 'header', 'query' ) ),
		] ),
		'url' => Types::url(),
	] );

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
			'url' => $url,
			'auth' => $auth,
			'slug' => sanitize_title( $display_name ),
		]);
	}

	public function to_ui_display(): array {
		return [
			'slug' => $this->get_slug(),
			'service' => REMOTE_DATA_BLOCKS_GENERIC_HTTP_SERVICE,
			'url' => $this->config['url'],
			'auth_type' => $this->config['auth']['type'],
			'uuid' => $this->config['uuid'] ?? null,
		];
	}
}
