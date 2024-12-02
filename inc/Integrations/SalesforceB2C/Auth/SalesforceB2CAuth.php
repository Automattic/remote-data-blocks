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
	public static function generate_token(
		string $endpoint,
		string $organization_id,
		string $client_id,
		string $client_secret
	): WP_Error|string {
		$saved_access_token = self::get_saved_access_token( $organization_id, $client_id );

		if ( null !== $saved_access_token ) {
			return $saved_access_token;
		}

		$saved_refresh_token = self::get_saved_refresh_token( $organization_id, $client_id );
		if ( null !== $saved_refresh_token ) {
			$access_token = self::get_token_using_refresh_token( $saved_refresh_token, $client_id, $client_secret, $endpoint, $organization_id );
		}

		if ( null !== $access_token ) {
			return $access_token;
		}

		$access_token = self::get_token_using_client_credentials( $client_id, $client_secret, $endpoint, $organization_id );
		return $access_token;
	}

	// Access token request using top-level credentials

	public static function get_token_using_client_credentials(
		string $client_id,
		string $client_secret,
		string $endpoint,
		string $organization_id,
	): WP_Error|string {
		$client_auth_url = sprintf( '%s/shopper/auth/v1/organizations/%s/oauth2/token', $endpoint, $organization_id );
		$client_credentials = base64_encode( sprintf( '%s:%s', $client_id, $client_secret ) );

		$client_auth_response = wp_remote_post( $client_auth_url, [
			'body' => [
				'grant_type' => 'client_credentials',
				'channel_id' => 'RefArch',
			],
			'headers' => [
				'Content-Type' => 'application/x-www-form-urlencoded',
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
		$access_token_expires_in = $response_data['expires_in'];
		self::save_access_token( $access_token, $access_token_expires_in, $organization_id, $client_id );

		$refresh_token = $response_data['refresh_token'];
		$refresh_token_expires_in = $response_data['refresh_token_expires_in'];
		self::save_refresh_token( $refresh_token, $refresh_token_expires_in, $organization_id, $client_id );

		return $access_token;
	}

	// Access token request using refresh token

	public static function get_token_using_refresh_token(
		string $refresh_token,
		string $client_id,
		string $client_secret,
		string $endpoint,
		string $organization_id,
	): ?string {
		$client_auth_url = sprintf( '%s/shopper/auth/v1/organizations/%s/oauth2/token', $endpoint, $organization_id );

		// Even though we're using a refresh token, authentication is still required to receive a new secret
		$client_credentials = base64_encode( sprintf( '%s:%s', $client_id, $client_secret ) );

		$client_auth_response = wp_remote_post( $client_auth_url, [
			'body' => [
				'grant_type' => 'refresh_token',
				'refresh_token' => $refresh_token,
				'channel_id' => 'RefArch',
			],
			'headers' => [
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Authorization' => 'Basic ' . $client_credentials,
			],
		]);

		if ( is_wp_error( $client_auth_response ) ) {
			return null;
		}

		$response_code = wp_remote_retrieve_response_code( $client_auth_response );
		$response_body = wp_remote_retrieve_body( $client_auth_response );
		$response_data = json_decode( $response_body, true );

		if ( 400 === $response_code ) {
			return null;
		}

		$access_token = $response_data['access_token'];
		$access_token_expires_in = $response_data['expires_in'];
		self::save_access_token( $access_token, $access_token_expires_in, $organization_id, $client_id );

		// No need to save the refresh token, as it stays the same until we perform a top-level authentication

		return $access_token;
	}

	// Access token cache management

	private static function save_access_token( string $access_token, int $expires_in, string $organization_id, string $client_id ): void {
		// Expires 10 seconds early as a buffer for request time and drift
		$access_token_expires_in = $expires_in - 10;

		// Debug
		$access_token_expires_in = 30;

		$access_token_data = [
			'token' => $access_token,
			'expires_at' => time() + $access_token_expires_in,
		];

		$access_token_cache_key = self::get_access_token_key( $organization_id, $client_id );

		wp_cache_set(
			$access_token_cache_key,
			$access_token_data,
			'oauth-tokens',
			// phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined -- 'expires_in' defaults to 30 minutes for access tokens.
			$access_token_expires_in,
		);
	}

	private static function get_saved_access_token( string $organization_id, string $client_id ): ?string {
		$access_token_cache_key = self::get_access_token_key( $organization_id, $client_id );

		$saved_access_token = wp_cache_get( $access_token_cache_key, 'oauth-tokens' );

		if ( false === $saved_access_token ) {
			return null;
		}

		$access_token = $saved_access_token['token'];
		$expires_at = $saved_access_token['expires_at'];

		// Ensure the token is still valid
		if ( time() >= $expires_at ) {
			return null;
		}

		return $access_token;
	}

	private static function get_access_token_key( string $organization_id, string $client_id ): string {
		$cache_key_suffix = hash( 'sha256', sprintf( '%s-%s', $organization_id, $client_id ) );
		return sprintf( 'salesforce_b2c_access_token_%s', $cache_key_suffix );
	}

	// Refresh token cache management

	private static function save_refresh_token( string $refresh_token, int $expires_in, string $organization_id, string $client_id ): void {
		// Expires 10 seconds early as a buffer for request time and drift
		$refresh_token_expires_in = $expires_in - 10;

		// Debug
		$refresh_token_expires_in = 120;

		$refresh_token_data = [
			'token' => $refresh_token,
			'expires_at' => time() + $refresh_token_expires_in,
		];

		$refresh_token_cache_key = self::get_refresh_token_key( $organization_id, $client_id );

		wp_cache_set(
			$refresh_token_cache_key,
			$refresh_token_data,
			'oauth-tokens',
			// phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined -- 'expires_in' defaults to 30 minutes for access tokens.
			$refresh_token_expires_in,
		);
	}

	private static function get_saved_refresh_token( string $organization_id, string $client_id ): ?string {
		$refresh_token_cache_key = self::get_refresh_token_key( $organization_id, $client_id );

		$saved_refresh_token = wp_cache_get( $refresh_token_cache_key, 'oauth-tokens' );

		if ( false === $saved_refresh_token ) {
			return null;
		}

		$refresh_token = $saved_refresh_token['token'];
		$expires_at = $saved_refresh_token['expires_at'];

		// Ensure the token is still valid
		if ( time() >= $expires_at ) {
			return null;
		}

		return $refresh_token;
	}

	private static function get_refresh_token_key( string $organization_id, string $client_id ): string {
		$cache_key_suffix = hash( 'sha256', sprintf( '%s-%s', $organization_id, $client_id ) );
		return sprintf( 'salesforce_b2c_refresh_token_%s', $cache_key_suffix );
	}
}
