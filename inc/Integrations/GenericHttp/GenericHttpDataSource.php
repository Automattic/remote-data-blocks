<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\GenericHttp;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;

class GenericHttpDataSource extends HttpDataSource {
	protected const SERVICE_NAME = REMOTE_DATA_BLOCKS_GENERIC_HTTP_SERVICE;
	protected const SERVICE_SCHEMA_VERSION = 1;
	
	protected const SERVICE_SCHEMA = [
		'type' => 'object',
		'properties' => [
			'service' => [
				'type' => 'string',
				'const' => REMOTE_DATA_BLOCKS_GENERIC_HTTP_SERVICE,
			],
			'service_schema_version' => [
				'type' => 'integer',
				'const' => self::SERVICE_SCHEMA_VERSION,
			],
			'auth' => [
				'type' => 'object',
				'properties' => [
					'type' => [
						'type' => 'string',
						'enum' => [ 'basic', 'bearer', 'api-key', 'none' ],
					],
					'value' => [
						'type' => 'string',
						'sanitize' => false,
					],
					'key' => [
						'type' => 'string',
						'sanitize' => false,
						'required' => false,
					],
					'add_to' => [
						'type' => 'string',
						'enum' => [ 'header', 'query' ],
						'required' => false,
					],
				],
			],
			'url' => [
				'type' => 'string',
				'callback' => '\RemoteDataBlocks\Validation\is_url',
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
            'display_name' => $display_name,
			'service' => REMOTE_DATA_BLOCKS_GENERIC_HTTP_SERVICE,
			'url' => $url,
			'auth' => $auth,
			'slug' => sanitize_title( $display_name ),
		]);
	}

	public function to_ui_display(): array {
		return [
            'display_name' => $this->get_display_name(),
			'slug' => $this->get_slug(),
			'service' => REMOTE_DATA_BLOCKS_GENERIC_HTTP_SERVICE,
			'url' => $this->config['url'],
			'auth_type' => $this->config['auth']['type'],
			'uuid' => $this->config['uuid'] ?? null,
		];
	}
}
