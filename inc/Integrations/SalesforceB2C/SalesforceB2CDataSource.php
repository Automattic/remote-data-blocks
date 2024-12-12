<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\SalesforceB2C;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Validation\Types;
use function plugins_url;

defined( 'ABSPATH' ) || exit();

class SalesforceB2CDataSource extends HttpDataSource {
	protected const SERVICE_NAME = REMOTE_DATA_BLOCKS_SALESFORCE_B2C_SERVICE;
	protected const SERVICE_SCHEMA_VERSION = 1;

	protected static function get_service_config_schema(): array {
		return Types::object( [
			'__version' => Types::integer(),
			'display_name' => Types::string(),
			'client_id' => Types::string(),
			'client_secret' => Types::string(),
			'organization_id' => Types::string(),
			'shortcode' => Types::string(),
		] );
	}

	protected static function map_service_config( array $service_config ): array {
		return [
			'endpoint' => sprintf( 'https://%s.api.commercecloud.salesforce.com', $service_config['shortcode'] ),
			'image_url' => plugins_url( './assets/salesforce_commerce_cloud_logo.png', __FILE__ ),
			'request_headers' => [
				'Content-Type' => 'application/json',
			],
		];
	}
}
