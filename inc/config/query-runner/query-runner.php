<?php

/**
 * QueryRunner class
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */

namespace RemoteDataBlocks\Config;

use Exception;
use GuzzleHttp\RequestOptions;
use RemoteDataBlocks\HttpClient;
use RemoteDataBlocks\Logging\LoggerManager;
use WP_Error;

defined( 'ABSPATH' ) || exit();

/**
 * Class that executes a query using QueryContext.
 */
class QueryRunner implements QueryRunnerInterface {

	public function __construct(
		private HttpQueryContext $query_context,
		private HttpClient $http_client = new HttpClient()
	) {
	}

	public function execute( array $input_variables ): array|WP_Error {
		$headers = $this->query_context->get_request_headers( $input_variables );
		$method  = $this->query_context->get_request_method();

		$body = $this->query_context->get_request_body( $input_variables );

		$endpoint = $this->query_context->get_endpoint( $input_variables );

		$parsed_url = wp_parse_url( $endpoint );

		if ( false === $parsed_url ) {
			return new WP_Error( 'Invalid endpoint URL parse' );
		}

		// Avoid PHP Warnings by setting expected keys to empty values.
		$parsed = array_merge( [
			'scheme'   => '',
			'host'     => '',
			'user'     => '',
			'pass'     => '',
			'port'     => '',
			'path'     => '',
			'query'    => '',
			'fragment' => '',
		], $parsed_url );

		if ( 'https' !== $parsed['scheme'] ) {
			return new WP_Error( 'Invalid endpoint URL scheme' );
		}
		$scheme = $parsed['scheme'];

		if ( empty( $parsed['host'] ) ) {
			return new WP_Error( 'Invalid endpoint URL host' );
		}
		$host = $parsed['host'];

		$user     = $parsed['user'] ?? '';
		$pass     = $parsed['pass'] ?? '';
		$port     = $parsed['port'] ? ':' . $parsed['port'] : '';
		$userpass = ( $user && $pass ) ? "$user:$pass@" : '';

		$path     = $parsed['path'] ?? '';
		$query    = $parsed['query'] ? '?' . $parsed['query'] : '';
		$fragment = $parsed['fragment'] ? '#' . $parsed['fragment'] : '';

		// Input for the HTTP client.
		$endpoint_base = "$scheme://$userpass$host$port";
		$uri           = "$path$query$fragment";
		$options       = [
			RequestOptions::HEADERS => array_merge( [
				'User-Agent' => 'WordPress Remote Data Blocks/1.0',
			], $headers ),
			RequestOptions::JSON    => $body,
		];

		$this->http_client->init( $endpoint_base );

		try {
			$response = $this->http_client->request( $method, $uri, $options );
		} catch ( Exception $e ) {
			return new WP_Error( 'remote-data-blocks-unexpected-exception', $e->getMessage() );
		}

		$response_code = $response->getStatusCode();

		if ( $response_code < 200 || $response_code >= 300 ) {
			return new WP_Error( 'remote-data-blocks-bad-status-code', $response->getReasonPhrase() );
		}

		// The body is a stream... if we need to read it in chunks, etc. we can do so here.
		$raw_response_data = $response->getBody()->getContents();

		if ( isset( $raw_response_data['errors'][0]['message'] ) ) {
			$logger = LoggerManager::instance();
			$logger->warning( sprintf( 'Query error: %s', esc_html( $raw_response_data['errors'][0]['message'] ) ) );
		}

		// Optionally process the raw response data using query context custom logic.
		$response_data = $this->query_context->process_response( $raw_response_data, $input_variables );

		// This method always returns an array, even if it's a single item. This
		// ensures a consistent response shape. The requestor is expected to inspect
		// is_collection and unwrap if necessary.
		$results = $this->map_fields( $response_data );

		return [
			'is_collection' => $this->query_context->is_collection(),
			'metadata'      => $this->query_context->get_metadata( $response, $results ),
			'results'       => $results,
		];
	}

	private function get_field_value( array|string $field_value, string $default_value = '', string $field_type = 'string' ): string {
		$field_value_single = is_array( $field_value ) && count( $field_value ) > 1
			? $field_value
			: ( $field_value[0] ?? $default_value );

		switch ( $field_type ) {
			case 'base64':
				return base64_decode( $field_value_single );

			case 'html':
				return $field_value_single;

			case 'price':
				return sprintf( '$%s', number_format( $field_value_single, 2 ) );

			case 'string':
				return wp_strip_all_tags( $field_value_single );
		}

		return $field_value_single;
	}

	private function map_fields( $response_data ): array|null {
		$root             = $response_data;
		$output_variables = $this->query_context->output_variables;

		if ( ! empty( $output_variables['root_path'] ) ) {
			$json = new JsonObject( $root );
			$root = $json->get( $output_variables['root_path'] );
		} else {
			$root = $this->query_context->is_collection() ? $root : [ $root ];
		}

		if ( empty( $root ) || empty( $output_variables['mappings'] ) ) {
			return $root;
		}

		// Loop over the returned items in the query result.
		return array_map( function ( $item ) use ( $output_variables ) {
			$json = new JsonObject( $item );

			// Loop over the output variables and extract the values from the item.
			$result = array_map( function ( $mapping ) use ( $json ) {
				if ( array_key_exists( 'generate', $mapping ) && is_callable( $mapping['generate'] ) ) {
					$field_value_single = $mapping['generate']( json_decode( $json->getJson(), true ) );
				} else {
					$field_path  = $mapping['path'] ?? null;
					$field_value = $field_path ? $json->get( $field_path ) : '';

					// JSONPath always returns values in an array, even if there's only one value.
					// Because we're mostly interested in single values for field mapping, unwrap the array if it's only one item.
					$field_value_single = self::get_field_value( $field_value, $mapping['defaultValue'] ?? '', $mapping['type'] );
				}

				return array_merge( $mapping, [
					'value' => $field_value_single,
				] );
			}, $output_variables['mappings'] );

			// Nest result property to reserve additional meta in the future.
			return [
				'result' => $result,
			];
		}, $root );
	}
}
