<?php declare(strict_types = 1);

namespace RemoteDataBlocks\REST;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\Logging\LoggerManager;
use RemoteDataBlocks\Store\DataSource\DataSourceConfigManager;
use RemoteDataBlocks\Integrations\SalesforceD2C\Auth\SalesforceD2CAuth;
use WP_Error;
use WP_REST_Request;
use function wp_generate_uuid4;
use WP_Http_Cookie;

class RemoteDataController {
	private static string $slug = 'remote-data';

	public static function init(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
	}

	public static function get_url(): string {
		$url = rest_url( sprintf( '/%s/%s', REMOTE_DATA_BLOCKS__REST_NAMESPACE, self::$slug ) );
		return add_query_arg( [ '_envelope' => 'true' ], $url );
	}

	public static function register_rest_routes(): void {
		register_rest_route( REMOTE_DATA_BLOCKS__REST_NAMESPACE, '/' . self::$slug, [
			'methods' => 'POST',
			'callback' => [ __CLASS__, 'execute_queries' ],
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'args' => [
				'block_name' => [
					'required' => true,
					'sanitize_callback' => function ( $value ) {
						return strval( $value );
					},
					'validate_callback' => function ( $value ) {
						return null !== ConfigStore::get_block_configuration( $value );
					},
				],
				'query_key' => [
					'required' => true,
					'sanitize_callback' => function ( $value ) {
						return strval( $value );
					},
				],
				'query_inputs' => [
					'required' => true,
					'validate_callback' => function ( $value ) {
						return is_array( $value );
					},
				],
			],
		] );

		register_rest_route( REMOTE_DATA_BLOCKS__REST_NAMESPACE, '/' . self::$slug . '/salesforce-d2c/generate-buyer-info', [
			'methods' => 'POST',
			'callback' => [ __CLASS__, 'execute_d2c_generate_buyer_info' ],
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'args' => [
				'uuid' => [
					'type' => 'string',
					'required' => true,
				],
			],
		] );

		register_rest_route( REMOTE_DATA_BLOCKS__REST_NAMESPACE, '/' . self::$slug . '/salesforce-d2c/proxy-request', [
			'methods' => 'POST',
			'callback' => [ __CLASS__, 'execute_salesforce_d2c_proxy_request' ],
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'args' => [
				'action' => [
					'type' => 'string',
					'required' => true,
				],
				'payload' => [
					'type' => 'object',
					'required' => true,
				],
			],
		] );
	}

	public static function execute_queries( WP_REST_Request $request ): array|WP_Error {
		$block_name = $request->get_param( 'block_name' );
		$query_key = $request->get_param( 'query_key' );
		$query_inputs = $request->get_param( 'query_inputs' );

		$block_config = ConfigStore::get_block_configuration( $block_name );
		$query = $block_config['queries'][ $query_key ];
		$query_response = $query->execute_batch( $query_inputs );

		if ( is_wp_error( $query_response ) ) {
			$logger = LoggerManager::instance();
			$logger->warning( $query_response->get_error_message() );
			return $query_response;
		}

		return array_merge(
			[
				'block_name' => $block_name,
				'result_id' => wp_generate_uuid4(),
				'query_key' => $query_key,
			],
			$query_response
		);
	}

	public static function execute_d2c_generate_buyer_info( WP_REST_Request $request ): array|WP_Error {
		$uuid = $request->get_param( 'uuid' );

		$data_source_config = DataSourceConfigManager::get( $uuid );

		if ( is_wp_error( $data_source_config ) ) {
			return $data_source_config;
		}

		if ( REMOTE_DATA_BLOCKS_SALESFORCE_D2C_SERVICE !== $data_source_config['service'] ) {
			return new WP_Error(
				'invalid_service',
				'Invalid service',
				[ 'status' => 400 ]
			);
		}

		$endpoint = 'https://' . $data_source_config['service_config']['domain'] . '.my.salesforce.com';

		return SalesforceD2CAuth::generate_guest_checkout_cookies( $endpoint, $data_source_config['service_config']['client_id'], $data_source_config['service_config']['client_secret'], $data_source_config['service_config']['store_id'] );
	}

	public static function execute_salesforce_d2c_proxy_request( WP_REST_Request $request ): array|WP_Error {
		$valid_actions = [
			'ADD_TO_CART',
			'GET_CART_ITEMS',
		];

		$action = $request->get_param( 'action' );
		$payload = $request->get_param( 'payload' );

		if ( ! in_array( $action, $valid_actions ) ) {
			return new WP_Error( 'invalid_action', 'Invalid action', [ 'status' => 400 ] );
		}

		$cookies = [];

		// Iterate over $_COOKIE and get the cookies that start with 'guest_uuid_essential_' or 'GuestCartSessionId_'
		foreach ( $_COOKIE as $cookie_name => $cookie_value ) {
			if ( str_starts_with( $cookie_name, 'guest_uuid_essential_' ) || str_starts_with( $cookie_name, 'GuestCartSessionId_' ) ) {
				$cookies[] = new WP_Http_Cookie( array(
					'name' => $cookie_name,
					'value' => $cookie_value,
				));
			}
		}

		if ( empty( $cookies ) && count( $cookies ) !== 2 ) {
			return new WP_Error( 'missing_cookies', 'Missing cookies', [ 'status' => 400 ] );
		}

		if ( ! isset( $payload['uuid'] ) ) {
			return new WP_Error( 'missing_uuid', 'Missing uuid', [ 'status' => 400 ] );
		}

		$data_source_config = DataSourceConfigManager::get( $payload['uuid'] );

		if ( is_wp_error( $data_source_config ) ) {
			return $data_source_config;
		}

		if ( REMOTE_DATA_BLOCKS_SALESFORCE_D2C_SERVICE !== $data_source_config['service'] ) {
			return new WP_Error(
				'invalid_service',
				'Invalid service',
				[ 'status' => 400 ]
			);
		}

		$endpoint = 'https://' . $data_source_config['service_config']['domain'] . '.my.salesforce.com';

		$buyer_endpoint = SalesforceD2CAuth::generate_buyer_endpoint( $endpoint, $data_source_config['service_config']['client_id'], $data_source_config['service_config']['client_secret'], $data_source_config['service_config']['store_id'] );

		if ( is_wp_error( $buyer_endpoint ) ) {
			return $buyer_endpoint;
		}

		switch ( $action ) {
			case 'ADD_TO_CART':
				return SalesforceD2CAuth::add_cart_item( $buyer_endpoint, $cookies, $payload );
			case 'GET_CART_ITEMS':
				return SalesforceD2CAuth::get_cart_items( $buyer_endpoint, $cookies, $payload );
			default:
				return new WP_Error( 'invalid_action', 'Invalid action', [ 'status' => 400 ] );
		}
	}

	public static function permission_callback(): bool {
		return true;
	}
}
