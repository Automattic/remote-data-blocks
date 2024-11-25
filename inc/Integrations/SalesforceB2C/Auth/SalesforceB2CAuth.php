<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\SalesforceB2C\Auth;

use WP_Error;

/**
 * Salesforce B2C Auth class.
 *
 * This class is used to authenticate with Salesforce B2C using a client ID and secret.
 */
class SalesforceB2CAuth {
	/**
	 * Generate a token from a client ID and secret, or use an existing token if available.
	 *
	 * @param string $endpoint The endpoint prefix URL for the data source,
	 * @param string $organization_id The organization ID for the data source.
	 * @param string $client_id The client ID (a version 4 UUID).
	 * @param string $client_secret The client secret.
	 * @return WP_Error|string The token or an error.
	 */
	public static function generate_token_from_client_credentials(
		string $endpoint,
		string $organization_id,
		string $client_id,
		string $client_secret
	): WP_Error|string {
		$client_auth_url = sprintf( '%s/shopper/auth/v1/organizations/%s/oauth2/login', $endpoint, $organization_id );
		$client_credentials = base64_encode( sprintf( '%s:%s', $client_id, $client_secret ) );

		$client_auth_response = wp_remote_post( $client_auth_url, [
			'body' => [
				'grant_type' => 'client_credentials',
				'channel_id' => 'RefArch',
			],
			'headers' => [
				'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
				'Authorization' => 'Basic ' . $client_credentials,
			],
		]);

		if ( is_wp_error( $client_auth_response ) ) {
			return new WP_Error(
				'salesforce_b2c_auth_error_client_credentials',
				__( 'Failed to retrieve access token from client credentials', 'remote-data-blocks' )
			);
		}


		$response_code = wp_remote_retrieve_response_code( $client_auth_response );
		$response_body = wp_remote_retrieve_body( $client_auth_response );
		$response_data = json_decode( $response_body, true );

		if ( 400 === $response_code ) {
			return new WP_Error(
				'salesforce_b2c_auth_error_client_credentials',
				/* translators: %s: Technical error message from API containing failure reason */
				sprintf( __( 'Failed to retrieve access token from client credentials: "%s"', 'remote-data-blocks' ), $response_data['message'] )
			);
		}

		$access_token = $response_data['access_token'];
		$expires_in = $response_data['expires_in'];

		$refresh_token = $response_data['refresh_token'];
		$refresh_token_expires_in = $response_data['refresh_token_expires_in'];

		return $access_token;

		// $cache_key = 'google_auth_token_' . $service_account_key->client_email;
		// if ( ! $no_cache ) {
		//  $cached_token = wp_cache_get( $cache_key, 'oauth-tokens' );
		//  if ( false !== $cached_token ) {
		//      return $cached_token;
		//  }
		// }

		// $jwt = self::generate_jwt( $service_account_key, $scope );
		// $token_uri = $service_account_key->token_uri;

		// $token = self::get_token_using_jwt( $jwt, $token_uri );

		// if ( ! is_wp_error( $token ) ) {
		//  wp_cache_set(
		//      $cache_key,
		//      $token,
		//      'oauth-tokens',
		//      3000, // 50 minutes
		//  );
		// }

		// return $token;
	}

	/**
	 * Get an access token using a JWT.
	 *
	 * @param string $jwt The JWT.
	 * @param string $token_uri The token URI.
	 * @return WP_Error|string The access token or an error.
	 */
	private static function get_token_using_jwt( string $jwt, string $token_uri ): WP_Error|string {
		$response = wp_remote_post(
			$token_uri,
			[
				'body' => [
					'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
					'assertion' => $jwt,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'google_auth_error',
				__( 'Failed to retrieve access token', 'remote-data-blocks' )
			);
		}

		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );

		if ( ! isset( $response_data['access_token'] ) ) {
			return new WP_Error(
				'google_auth_error',
				__( 'Invalid response from Google Auth', 'remote-data-blocks' )
			);
		}

		return $response_data['access_token'];
	}

	/**
	 * Generate a JWT.
	 *
	 * @param GoogleServiceAccountKey $service_account_key The service account key.
	 * @param string $scope The scope.
	 * @return string The JWT.
	 */
	private static function generate_jwt(
		GoogleServiceAccountKey $service_account_key,
		string $scope
	): string {
		$header = self::generate_jwt_header();
		$payload = self::generate_jwt_payload(
			$service_account_key->client_email,
			$service_account_key->token_uri,
			$scope
		);

		$base64_url_header = base64_encode( wp_json_encode( $header ) );
		$base64_url_payload = base64_encode( wp_json_encode( $payload ) );

		$signature = self::generate_jwt_signature(
			$base64_url_header,
			$base64_url_payload,
			$service_account_key->private_key
		);
		$base64_url_signature = base64_encode( $signature );

		return $base64_url_header . '.' . $base64_url_payload . '.' . $base64_url_signature;
	}

	/**
	 * Generate a JWT signature.
	 *
	 * @param string $base64_url_header The base64 URL encoded header.
	 * @param string $base64_url_payload The base64 URL encoded payload.
	 * @param string $private_key The private key.
	 * @return string The JWT signature.
	 */
	private static function generate_jwt_signature(
		string $base64_url_header,
		string $base64_url_payload,
		string $private_key
	): string {
		$signature_input = $base64_url_header . '.' . $base64_url_payload;

		openssl_sign( $signature_input, $signature, $private_key, 'sha256' );
		return $signature;
	}

	/**
	 * Generate a JWT header.
	 *
	 * @return array The JWT header.
	 */
	private static function generate_jwt_header(): array {
		$header = [
			'alg' => 'RS256',
			'typ' => 'JWT',
		];

		return $header;
	}

	/**
	 * Generate a JWT payload.
	 *
	 * @param string $client_email The client email.
	 * @param string $token_uri The token URI.
	 * @param string $scope The scope.
	 * @return array The JWT payload.
	 */
	private static function generate_jwt_payload(
		string $client_email,
		string $token_uri,
		string $scope
	): array {
		$now = time();
		$expiry = $now + self::TOKEN_EXPIRY_SECONDS;

		$payload = [
			'iss' => $client_email,
			'scope' => $scope,
			'aud' => $token_uri,
			'exp' => $expiry,
			'iat' => $now,
		];

		return $payload;
	}
}
