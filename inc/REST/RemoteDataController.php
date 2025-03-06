<?php declare(strict_types = 1);

namespace RemoteDataBlocks\REST;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\Logging\LoggerManager;
use RemoteDataBlocks\Store\DataSource\DataSourceConfigManager;
use RemoteDataBlocks\Integrations\SalesforceD2C\Auth\SalesforceD2CAuth;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use function wp_generate_uuid4;

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
			'callback' => [ __CLASS__, 'execute_query' ],
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
				'query_input' => [
					'required' => true,
					'validate_callback' => function ( $value ) {
						return is_array( $value );
					},
				],
			],
		] );

		register_rest_route( REMOTE_DATA_BLOCKS__REST_NAMESPACE, '/' . self::$slug . '/salesforce-d2c/buyer', [
			'methods' => 'POST',
			'callback' => [ __CLASS__, 'execute_salesforce_d2c_buyer_query' ],
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'args' => [
				'uuid' => [
					'type' => 'string',
					'required' => true,
				],
			],
		] );

		register_rest_route( REMOTE_DATA_BLOCKS__REST_NAMESPACE, '/' . self::$slug . '/salesforce-d2c/add-item-to-cart', [
			'methods' => 'POST',
			'callback' => [ __CLASS__, 'execute_salesforce_d2c_add_item_to_cart_query' ],
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'args' => [
				'cartId' => [
					'type' => 'string',
					'required' => true,
				],
				'productId' => [
					'type' => 'string',
					'required' => true,
				],
				'quantity' => [
					'type' => 'integer',
					'required' => true,
				],
			],
		] );
	}

	public static function execute_query( WP_REST_Request $request ): array|WP_Error {
		$block_name = $request->get_param( 'block_name' );
		$query_key = $request->get_param( 'query_key' );
		$query_input = $request->get_param( 'query_input' );

		$block_config = ConfigStore::get_block_configuration( $block_name );
		$query = $block_config['queries'][ $query_key ];

		// The frontend might send more input variables than the query needs or
		// expects, so only include those defined by the query.
		$query_input = array_intersect_key( $query_input, $query->get_input_schema() );

		$query_result = $query->execute( $query_input );

		if ( is_wp_error( $query_result ) ) {
			$logger = LoggerManager::instance();
			$logger->warning( $query_result->get_error_message() );
			return $query_result;
		}

		return array_merge(
			[
				'block_name' => $block_name,
				'result_id' => wp_generate_uuid4(),
				'query_key' => $query_key,
				'query_input' => $query_input,
			],
			$query_result
		);
	}

	public static function execute_salesforce_d2c_buyer_query( WP_REST_Request $request ): array|WP_Error {
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

		$token = SalesforceD2CAuth::generate_token( $endpoint, $data_source_config['service_config']['client_id'], $data_source_config['service_config']['client_secret'] );

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		return SalesforceD2CAuth::generate_guest_checkout_cookies( $endpoint, $token, $data_source_config['service_config']['store_id'] );
	}

	public static function execute_salesforce_d2c_add_item_to_cart_query( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		// Check if the request has the necessary cookies.
		if ( ! isset( $_COOKIE['guest_uuid_essential_'] ) ) {
			return new WP_Error(
				'missing_cookies',
				'Missing cookies',
				[ 'status' => 400 ]
			);
		}

		$cartId = $request->get_param( 'cartId' );
		$productId = $request->get_param( 'productId' );
		$quantity = $request->get_param( 'quantity' );

		return rest_ensure_response( "done" );
	}

	public static function permission_callback(): bool {
		return true;
	}
}
