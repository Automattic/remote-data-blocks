<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\SalesforceD2C\Auth;

use WP_Error;

/**
 * Salesforce D2C Auth class.
 *
 * This class is used to authenticate with Salesforce D2C using a client ID and secret.
 */
class SalesforceD2CAuth {

	/**
	 * Generate a token from a client ID and secret, or use an existing token if available.
	 *
	 * @param string $endpoint The endpoint prefix URL for the data source,
	 * @param string $organization_id The organization ID for the data source.
	 * @param string $client_id The client ID (a version 4 UUID).
	 * @param string $client_secret The client secret.
	 * @return string|WP_Error The token or an error.
	 */
	public static function generate_token(
		string $endpoint,
		string $client_id,
		string $client_secret
	): string|WP_Error {
		$saved_access_token = self::get_saved_access_token( $client_id );

		if ( null !== $saved_access_token ) {
			return $saved_access_token;
		}

		$access_token = self::get_token_using_client_credentials( $client_id, $client_secret, $endpoint );
		return $access_token;
	}

	/**
	 * Get a token using client credentials.
	 *
	 * @param string $client_id The client ID.
	 * @param string $client_secret The client secret.
	 * @param string $endpoint The endpoint prefix URL for the data source.
	 * @return WP_Error|string The token or an error.
	 */
	public static function get_token_using_client_credentials(
		string $client_id,
		string $client_secret,
		string $endpoint,
	): WP_Error|string {
		$client_auth_url = sprintf( '%s/services/oauth2/token', $endpoint );

		$client_auth_url = add_query_arg( [
			'grant_type' => 'client_credentials',
			'client_id' => $client_id,
			'client_secret' => $client_secret,
		], $client_auth_url );

		$client_auth_response = wp_remote_post($client_auth_url, [
			'headers' => [
				'Content-Type' => 'application/x-www-form-urlencoded',
			],
		]);

		if ( is_wp_error( $client_auth_response ) ) {
			return new WP_Error(
				'salesforce_d2c_auth_error_client_credentials',
				__( 'Failed to retrieve access token from client credentials', 'remote-data-blocks' )
			);
		}

		$response_code = wp_remote_retrieve_response_code( $client_auth_response );
		$response_body = wp_remote_retrieve_body( $client_auth_response );
		$response_data = json_decode( $response_body, true );

		if ( 400 === $response_code || 401 === $response_code ) {
			return new WP_Error(
				'salesforce_d2c_auth_error_client_credentials',
				/* translators: %s: Technical error message from API containing failure reason */
				sprintf( __( 'Failed to retrieve access token from client credentials: "%s"', 'remote-data-blocks' ), $response_data['message'] )
			);
		}

		$access_token = $response_data['access_token'];
		self::save_access_token( $access_token, $client_id );

		return $access_token;
	}

	private static function save_access_token( string $access_token, string $client_id ): void {
		$access_token_data = [
			'token' => $access_token,
		];

		$access_token_cache_key = self::get_access_token_key( $client_id );

		wp_cache_set(
			$access_token_cache_key,
			$access_token_data,
			'oauth-tokens',
		);
	}

	private static function get_saved_access_token( string $client_id ): ?string {
		$access_token_cache_key = self::get_access_token_key( $client_id );

		$saved_access_token = wp_cache_get( $access_token_cache_key, 'oauth-tokens' );

		if ( false === $saved_access_token ) {
			return null;
		}

		$access_token = $saved_access_token['token'];

		return $access_token;
	}

	private static function get_access_token_key( string $client_id ): string {
		$cache_key_suffix = hash( 'sha256', sprintf( '%s', $client_id ) );
		return sprintf( 'salesforce_d2c_access_token_%s', $cache_key_suffix );
	}
}
