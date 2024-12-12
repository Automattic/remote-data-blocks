<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\SalesforceB2C;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;

use function plugins_url;

defined( 'ABSPATH' ) || exit();

class SalesforceB2CDataSource extends HttpDataSource {
	protected const SERVICE_NAME = REMOTE_DATA_BLOCKS_SALESFORCE_B2C_SERVICE;
	protected const SERVICE_SCHEMA_VERSION = 1;

	protected const SERVICE_SCHEMA = [
		'type' => 'object',
		'properties' => [
			'service' => [
				'type' => 'string',
				'const' => REMOTE_DATA_BLOCKS_SALESFORCE_B2C_SERVICE,
			],
			'service_schema_version' => [
				'type' => 'integer',
				'const' => self::SERVICE_SCHEMA_VERSION,
			],
			'shortcode' => [ 'type' => 'string' ],
			'organization_id' => [ 'type' => 'string' ],
			'client_id' => [ 'type' => 'string' ],
			'client_secret' => [ 'type' => 'string' ],
			'display_name' => [ 'type' => 'string', 'required' => true ],
		],
	];

	public function get_display_name(): string {
		return 'Salesforce B2C (' . $this->config['uuid'] . ')';
	}

	public function get_endpoint(): string {
		return sprintf( 'https://%s.api.commercecloud.salesforce.com', $this->config['shortcode'] );
	}

	public function get_request_headers(): array {
		return [
			'Content-Type' => 'application/json',
		];
	}

	public function get_image_url(): string {
		return plugins_url( './assets/salesforce_commerce_cloud_logo.png', __FILE__ );
	}

	public static function create( string $shortcode, string $organization_id, string $client_id, string $client_secret, ?string $display_name = null ): self {
		return parent::from_array([
			'service' => REMOTE_DATA_BLOCKS_SALESFORCE_B2C_SERVICE,
			'shortcode' => $shortcode,
			'organization_id' => $organization_id,
			'client_id' => $client_id,
			'client_secret' => $client_secret,
			'display_name' => $display_name
		]);
	}

	public function to_ui_display(): array {
		return [
			'service' => REMOTE_DATA_BLOCKS_SALESFORCE_B2C_SERVICE,
			'store_name' => $this->config['store_name'],
			'uuid' => $this->config['uuid'] ?? null,
		];
	}
}
