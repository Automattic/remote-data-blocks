<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\GenericHttp;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Validation\Types;
use RemoteDataBlocks\Validation\Validator;
use WP_Error;

class GenericHttpDataSource extends HttpDataSource {
	protected const SERVICE_NAME = REMOTE_DATA_BLOCKS_GENERIC_HTTP_SERVICE;
	protected const SERVICE_SCHEMA_VERSION = 1;

	public static function create( array $service_config, array $config_overrides = [] ): self|WP_Error {
		$validator = new Validator( self::get_service_config_schema() );
		$validated = $validator->validate( $service_config );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		return self::from_array(
			array_merge(
				[
					'display_name' => sprintf( 'HTTP Connection (%s)', $service_config['slug'] ),
					'endpoint' => $service_config['url'],
					'service' => REMOTE_DATA_BLOCKS_GENERIC_HTTP_SERVICE,
					'service_config' => $service_config,
					'slug' => $service_config['slug'],
				],
				$config_overrides
			)
		);
	}

	private static function get_service_config_schema(): array {
		return Types::object( [
			'auth' => Types::nullable(
				Types::object( [
					'add_to' => Types::nullable( Types::enum( 'header', 'query' ) ),
					'key' => Types::nullable( Types::unsanitizable( Types::string() ) ),
					'type' => Types::enum( 'basic', 'bearer', 'api-key', 'none' ),
					'value' => Types::unsanitizable( Types::string() ),
				] )
			),
			'slug' => Types::string(),
			'url' => Types::url(),
		] );
	}

	public function to_ui_display(): array {
		return array_merge(
			parent::to_ui_display(),
			[
				'auth_type' => $this->config['service_config']['auth']['type'] ?? null,
				'url' => $this->get_endpoint(),
			]
		);
	}
}
