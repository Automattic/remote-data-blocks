<?php

namespace RemoteDataBlocks\Integrations\Google\Auth;

use WP_Error;
use RemoteDataBlocks\Integrations\Google\Auth\GoogleServiceAccountKey;

class GoogleAuth {
	const TOKEN_EXPIRY_SECONDS = 3600; // 1 hour
	/**
	 * The whitelisted scopes that are allowed to be used when generating a token.
	 * This avoids this being used to get tokens with broad scopes.
	 */
	const ALLOWED_SCOPES = [
		'https://www.googleapis.com/auth/drive.readonly', // Drive Readonly
		'https://www.googleapis.com/auth/spreadsheets.readonly', // Sheets Readonly
	];

	const GOOGLE_SHEETS_SCOPES = [
		'https://www.googleapis.com/auth/drive.readonly', // Drive Readonly
		'https://www.googleapis.com/auth/spreadsheets.readonly', // Sheets Readonly
	];

	private static function get_allowed_scopes( array $scopes ): array {
		return array_values( array_intersect( $scopes, self::ALLOWED_SCOPES ) );
	}

	/**
	 * Generate a token from a service account key.
	 *
	 * @param array $raw_service_account_key The service account key.
	 * @param array $scopes The scopes to generate the token for.
	 * @return WP_Error|string The token or an error.
	 */
	public static function generate_token_from_service_account_key(
		array $raw_service_account_key,
		array $scopes,
		bool $no_cache = false
	): WP_Error|string {
		$filtered_scopes = self::get_allowed_scopes( $scopes );

		if ( empty( $filtered_scopes ) ) {
			return new WP_Error(
				'google_auth_error',
				__( 'No valid scopes provided', 'remote-data-blocks' )
			);
		}

		$scope = implode( ' ', $filtered_scopes );

		$service_account_key = GoogleServiceAccountKey::from_array( $raw_service_account_key );
		if ( is_wp_error( $service_account_key ) ) {
			return $service_account_key;
		}

		$cache_key = 'google_auth_token_' . $service_account_key->client_email;
		if ( ! $no_cache ) {
			$cached_token = wp_cache_get( $cache_key, 'oauth-tokens' );
			if ( false !== $cached_token ) {
				return $cached_token;
			}
		}

		$jwt       = self::generate_jwt( $service_account_key, $scope );
		$token_uri = $service_account_key->token_uri;

		$token = self::get_token_using_jwt( $jwt, $token_uri );

		if ( ! is_wp_error( $token ) ) {
			wp_cache_set(
				$cache_key,
				$token,
				'oauth-tokens',
				3000, // 50 minutes
			);
		}

		return $token;
	}

	private static function get_token_using_jwt( string $jwt, string $token_uri ): WP_Error|string {
		$response = wp_remote_post(
			$token_uri,
			[
				'body' => [
					'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
					'assertion'  => $jwt,
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

	private static function generate_jwt(
		GoogleServiceAccountKey $service_account_key,
		string $scope
	): string {
		$header  = self::generate_jwt_header();
		$payload = self::generate_jwt_payload(
			$service_account_key->client_email,
			$service_account_key->token_uri,
			$scope
		);

		$base64_url_header  = base64_encode( wp_json_encode( $header ) );
		$base64_url_payload = base64_encode( wp_json_encode( $payload ) );

		$signature            = self::generate_jwt_signature(
			$base64_url_header,
			$base64_url_payload,
			$service_account_key->private_key
		);
		$base64_url_signature = base64_encode( $signature );

		return $base64_url_header . '.' . $base64_url_payload . '.' . $base64_url_signature;
	}

	private static function generate_jwt_signature(
		string $base64_url_header,
		string $base64_url_payload,
		string $private_key
	): string {
		$signature_input = $base64_url_header . '.' . $base64_url_payload;

		openssl_sign( $signature_input, $signature, $private_key, 'sha256' );
		return $signature;
	}

	private static function generate_jwt_header(): array {
		$header = [
			'alg' => 'RS256',
			'typ' => 'JWT',
		];

		return $header;
	}

	private static function generate_jwt_payload(
		string $client_email,
		string $token_uri,
		string $scope
	): array {
		$now    = time();
		$expiry = $now + self::TOKEN_EXPIRY_SECONDS;

		$payload = [
			'iss'   => $client_email,
			'scope' => $scope,
			'aud'   => $token_uri,
			'exp'   => $expiry,
			'iat'   => $now,
		];

		return $payload;
	}
}
