<?php

namespace RemoteDataBlocks\REST;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Editor\ConfigurationLoader;
use RemoteDataBlocks\Logging\LoggerManager;
use WP_Error;
use WP_REST_Request;
use function wp_generate_uuid4;

class RemoteData {
	private static $slug = 'remote-data';

	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
	}

	public static function get_url() {
		$url = rest_url( sprintf( '/%s/%s', REMOTE_DATA_BLOCKS__REST_NAMESPACE, self::$slug ) );
		return add_query_arg( [ '_envelope' => 'true' ], $url );
	}

	public static function register_rest_routes() {
		register_rest_route( REMOTE_DATA_BLOCKS__REST_NAMESPACE, '/' . self::$slug, [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'execute_query' ],
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'args'                => [
				'block_name'  => [
					'required'          => true,
					'sanitize_callback' => function ( $value ) {
						return strval( $value );
					},
					'validate_callback' => function ( $value ) {
						return null !== ConfigurationLoader::get_configuration( $value );
					},
				],
				'query_key'   => [
					'required'          => true,
					'sanitize_callback' => function ( $value ) {
						return strval( $value );
					},
				],
				'query_input' => [
					'required'          => true,
					'validate_callback' => function ( $value ) {
						return is_array( $value );
					},
				],
			],
		] );
	}

	public static function execute_query( WP_REST_Request $request ): array|WP_Error {
		$block_name  = $request->get_param( 'block_name' );
		$query_key   = $request->get_param( 'query_key' );
		$query_input = $request->get_param( 'query_input' );

		$block_config = ConfigurationLoader::get_configuration( $block_name );
		$query        = $block_config['queries'][ $query_key ];

		$query_runner = $query->get_query_runner();
		$query_result = $query_runner->execute( $query_input );

		if ( is_wp_error( $query_result ) ) {
			$logger = LoggerManager::instance();
			$logger->warning( $query_result->get_error_message() );
			return $query_result;
		}

		return array_merge(
			[
				'block_name'  => $block_name,
				'result_id'   => wp_generate_uuid4(),
				'query_key'   => $query_key,
				'query_input' => $query_input,
			],
			$query_result
		);
	}

	public static function permission_callback() {
		return true;
	}
}
