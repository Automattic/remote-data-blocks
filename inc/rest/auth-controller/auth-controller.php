<?php

namespace RemoteDataBlocks\REST;

use WP_REST_Controller;
use WP_REST_Request;
use RemoteDataBlocks\Config\Auth\GoogleAuth;

defined( 'ABSPATH' ) || exit();
defined( 'ABSPATH' ) || exit();

class AuthController extends WP_REST_Controller {
	public function __construct() {
		$this->namespace = REMOTE_DATA_BLOCKS__REST_NAMESPACE;
		$this->rest_base = 'auth';
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/google/token',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'get_google_auth_token' ],
				'permission_callback' => [ $this, 'get_google_auth_token_permissions_check' ],
			]
		);
	}

	public function get_google_auth_token( WP_REST_Request $request ) {
		$params      = $request->get_json_params();
		$credentials = $params['credentials'] ?? null;
		$scopes      = $params['scopes'] ?? [];
		$type        = $params['type'] ?? null;

		if ( ! $credentials || ! $type || ! $scopes ) {
			return new \WP_Error(
				'missing_parameters',
				__( 'Credentials, type and scopes are required.', 'remote-data-blocks' ),
				array( 'status' => 400 )
			);
		}

		if ( 'service_account' === $type ) {
			$token = GoogleAuth::generate_token_from_service_account_key( $credentials, $scopes );
			if ( is_wp_error( $token ) ) {
				return rest_ensure_response( $token );
			}
			return rest_ensure_response( [ 'token' => $token ] );
		}

		return new \WP_Error(
			'invalid_type',
			__( 'Invalid type. Supported types: service_account', 'remote-data-blocks' ),
			array( 'status' => 400 )
		);
	}

	public function get_google_auth_token_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}
}
