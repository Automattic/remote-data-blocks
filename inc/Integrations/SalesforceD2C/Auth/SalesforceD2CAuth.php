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
	 * @param string $client_id The client ID (a version 4 UUID).
	 * @param string $client_secret The client secret.
	 * @return string|WP_Error The token or an error.
	 */
	public static function generate_token(
		string $endpoint,
		string $client_id,
		string $client_secret
	): string|WP_Error {
		return self::get_saved_access_token( $client_id ) ?? self::get_token_using_client_credentials( $client_id, $client_secret, $endpoint );
	}

	public static function generate_buyer_endpoint(
		string $endpoint,
		string $token,
		string $store_id,
	): array|WP_Error {
		// First we need to get the base store url.
		// ToDo: Figure out how we can narrow this based on the store_id rather than using LIMIT 1.
		$domain_url = self::get_saved_domain( $store_id ) ?? self::get_domain_url( $endpoint, $token, $store_id );

		if ( is_wp_error( $domain_url ) ) {
			return $domain_url;
		}

		// Then, we need to get the store prefix as well as the site id to use for the buyer cookie.
		$site_details = self::get_saved_site_details( $store_id ) ?? self::get_site_details( $endpoint, $token, $store_id );

		if ( is_wp_error( $site_details ) ) {
			return $site_details;
		}

		// Generate the buyer url and the cookie that would be used for guest buyer shopping.
		// ToDo: site_url_path_prefix is not always present, so we need to handle that.
		$buyer_endpoint = sprintf( '%s/%s/webruntime/api/services/data/v63.0/commerce/webstores/%s', $domain_url, $site_details['site_url_path_prefix'], $store_id );
		$buyer_cookie = sprintf( 'guest_uuid_essential_%s', substr( $site_details['site_id'], 0, 15 ) );

		return [
			'buyer_endpoint' => $buyer_endpoint,
			'buyer_cookie' => $buyer_cookie,
		];
	}

	public static function get_domain_url(
		string $endpoint,
		string $token,
		string $store_id,
	): string|WP_Error {
		// First we need to get the base store url.
		// ToDo: Figure out how we can narrow this based on the store_id rather than using LIMIT 1.
		$domain_url = sprintf( '%s/services/data/v63.0/query/?q=SELECT+domain+from+domain+LIMIT+1', $endpoint, $store_id );

		$response = wp_remote_get( $domain_url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
			],
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			return new WP_Error(
				'salesforce_d2c_auth_error_domain',
				__( 'Failed to retrieve domain', 'remote-data-blocks' )
			);
		}

		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );

		if ( ! isset( $response_data['records'] ) ) {
			return new WP_Error(
				'salesforce_d2c_auth_error_domain',
				__( 'Failed to retrieve domain', 'remote-data-blocks' )
			);
		}

		foreach ( $response_data['records'] as $record ) {
			if ( isset( $record['Domain'] ) ) {
				$domain = sprintf( 'https://%s', $record['Domain'] );
				self::save_domain( $domain, $store_id );
				return $domain;
			}
		}

		return new WP_Error(
			'salesforce_d2c_auth_error_domain',
			__( 'Failed to retrieve domain', 'remote-data-blocks' )
		);
	}

	public static function get_site_details(
		string $endpoint,
		string $token,
		string $store_id,
	): array|WP_Error {
		$site_details_url = sprintf( "%s/services/data/v63.0/query/?q=select+site.id,+site.name,+site.urlPathPrefix+from+WebstoreNetwork+where+webstoreId='%s'", $endpoint, $store_id );

		$response = wp_remote_get( $site_details_url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
			],
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			return new WP_Error(
				'salesforce_d2c_auth_error_site_details',
				__( 'Failed to retrieve site details', 'remote-data-blocks' )
			);
		}

		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );

		if ( ! isset( $response_data['records'] ) ) {
			return new WP_Error(
				'salesforce_d2c_auth_error_site_details',
				__( 'Failed to retrieve site details', 'remote-data-blocks' )
			);
		}

		foreach ( $response_data['records'] as $record ) {
			if ( isset( $record['Site'] ) && isset( $record['Site']['Id'] ) && isset( $record['Site']['UrlPathPrefix'] ) ) {
				$site_id = $record['Site']['Id'];
				$site_url_path_prefix = $record['Site']['UrlPathPrefix'];

				self::save_site_details( $site_id, $site_url_path_prefix, $store_id );

				return [
					'site_id' => $site_id,
					'site_url_path_prefix' => $site_url_path_prefix,
				];
			}
		}

		return new WP_Error(
			'salesforce_d2c_auth_error_site_details',
			__( 'Failed to retrieve site details', 'remote-data-blocks' )
		);
	}

	/**
	 * Get the webstores using the given endpoint, and token.
	 *
	 * @param string $endpoint The endpoint prefix URL for the data source.
	 * @param string $token The token.
	 * @return array|WP_Error The webstores or an error.
	 */
	public static function get_webstores(
		string $endpoint,
		string $token,
	): array|WP_Error {
		$webstores_url = sprintf( '%s/services/data/v63.0/query/?q=SELECT+name,id+from+webstore', $endpoint );

		/* phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get -- We don't only work on VIP so we can't rely on that. That said, we should safely implement a wrapper that uses it when it's available. */
		$response = wp_remote_get( $webstores_url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
			],
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			return new WP_Error(
				'salesforce_d2c_auth_error_webstores',
				__( 'Failed to retrieve webstores', 'remote-data-blocks' )
			);
		}

		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );

		$records = $response_data['records'] ?? [];

		return array_map( function ( $record ) {
			return [
				'id' => $record['Id'],
				'name' => $record['Name'],
			];
		}, $records );
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

		if ( 200 !== $response_code ) {
			return new WP_Error(
				'salesforce_d2c_auth_error_client_credentials',
				/* translators: %s: Technical error message from API containing failure reason */
				sprintf( __( 'Failed to retrieve access token from client credentials: "%s"', 'remote-data-blocks' ), $response_data['message'] )
			);
		}

		$access_token = $response_data['access_token'];

		$client_introspect_url = sprintf( '%s/services/oauth2/introspect', $endpoint );
		$client_credentials = base64_encode( sprintf( '%s:%s', $client_id, $client_secret ) );


		$client_introspect_response = wp_remote_post($client_introspect_url, [
			'body' => [
				'token' => $access_token,
			],
			'headers' => [
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Authorization' => 'Basic ' . $client_credentials,
			],
		]);

		if ( is_wp_error( $client_introspect_response ) ) {
			return new WP_Error(
				'salesforce_d2c_auth_error_client_credentials',
				__( 'Failed to introspect token', 'remote-data-blocks' )
			);
		}

		$response_code = wp_remote_retrieve_response_code( $client_introspect_response );
		$response_body = wp_remote_retrieve_body( $client_introspect_response );
		$response_data = json_decode( $response_body, true );

		if ( 400 === $response_code || 401 === $response_code ) {
			return new WP_Error(
				'salesforce_d2c_auth_error_client_credentials',
				/* translators: %s: Technical error message from API containing failure reason */
				sprintf( __( 'Failed to introspect token: "%s"', 'remote-data-blocks' ), $response_data['message'] )
			);
		}

		$expiry_time = $response_data['exp'];

		self::save_access_token( $access_token, $client_id, $expiry_time );

		return $access_token;
	}

	private static function save_site_details( string $site_id, string $site_url_path_prefix, string $store_id ): void {
		$cache_key = self::get_site_details_key( $store_id );
		wp_cache_set(
			$cache_key,
			[
				'site_id' => $site_id,
				'site_url_path_prefix' => $site_url_path_prefix,
			],
			'salesforce-d2c-site-details',
			// phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined -- 'expires_in' defaults to 30 minutes for access tokens.
			time() + 86400,
		);
	}

	private static function save_domain( string $domain, string $store_id ): void {
		$cache_key = self::get_domain_key( $store_id );
		wp_cache_set(
			$cache_key,
			[
				'domain' => $domain,
			],
			'salesforce-d2c-domain',
			// phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined -- 'expires_in' defaults to 30 minutes for access tokens.
			time() + 86400,
		);
	}

	private static function get_saved_site_details( string $store_id ): ?array {
		$cache_key = self::get_site_details_key( $store_id );
		$saved_site_details = wp_cache_get( $cache_key, 'salesforce-d2c-site-details' );

		if ( false === $saved_site_details ) {
			return null;
		}

		return $saved_site_details ?? null;
	}

	private static function get_saved_domain( string $store_id ): ?string {
		$cache_key = self::get_domain_key( $store_id );
		$saved_domain = wp_cache_get( $cache_key, 'salesforce-d2c-domain' );

		if ( false === $saved_domain ) {
			return null;
		}

		return $saved_domain['domain'] ?? null;
	}

	private static function get_domain_key( string $store_id ): string {
		$cache_key_suffix = hash( 'sha256', sprintf( '%s', $store_id ) );
		return sprintf( 'salesforce_d2c_domain_%s', $cache_key_suffix );
	}

	private static function get_site_details_key( string $store_id ): string {
		$cache_key_suffix = hash( 'sha256', sprintf( '%s', $store_id ) );
		return sprintf( 'salesforce_d2c_site_details_%s', $cache_key_suffix );
	}

	private static function save_access_token( string $access_token, string $client_id, int $expiry_time ): void {
		// Get the time 10 seconds before the token expires.
		// Note that, the expiry time is a unix timestamp and so we need to subtract the current time from it.
		$access_token_expiry_time = $expiry_time - time() - 10;

		$access_token_data = [
			'token' => $access_token,
		];

		$access_token_cache_key = self::get_access_token_key( $client_id );

		wp_cache_set(
			$access_token_cache_key,
			$access_token_data,
			'oauth-tokens',
			// phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined -- 'expires_in' defaults to 30 minutes for access tokens.
			$access_token_expiry_time,
		);
	}

	private static function get_saved_access_token( string $client_id ): ?string {
		$access_token_cache_key = self::get_access_token_key( $client_id );

		$saved_access_token = wp_cache_get( $access_token_cache_key, 'oauth-tokens' );

		if ( false === $saved_access_token ) {
			return null;
		}

		return $saved_access_token['token'] ?? null;
	}

	private static function get_access_token_key( string $client_id ): string {
		$cache_key_suffix = hash( 'sha256', sprintf( '%s', $client_id ) );
		return sprintf( 'salesforce_d2c_access_token_%s', $cache_key_suffix );
	}
}
