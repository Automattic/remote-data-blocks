<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\QueryRunner;

use Exception;
use GuzzleHttp\RequestOptions;
use JsonPath\JsonObject;
use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\HttpClient\HttpClient;
use WP_Error;

defined( 'ABSPATH' ) || exit();

/**
 * QueryRunner class
 *
 * Class that executes queries, leveraging provided QueryContext.
 *
 */
class QueryRunner implements QueryRunnerInterface {

	public function __construct(
		private HttpQueryContext $query_context,
		private HttpClient $http_client = new HttpClient()
	) {
	}

	/**
	 * Get the HTTP request details for the query
	 *
	 * @param array<string, mixed> $input_variables The input variables for the current request.
	 * @return WP_Error|array{
	 *   method: string,
	 *   options: array<string, mixed>,
	 *   origin: string,
	 *   ttl: int|null,
	 *   uri: string,
	 * } The request details.
	 */
	protected function get_request_details( array $input_variables ): array|WP_Error {
		$headers = $this->query_context->get_request_headers( $input_variables );
		$method = $this->query_context->get_request_method();
		$body = $this->query_context->get_request_body( $input_variables );
		$endpoint = $this->query_context->get_endpoint( $input_variables );
		$cache_ttl = $this->query_context->get_cache_ttl( $input_variables );

		$parsed_url = wp_parse_url( $endpoint );

		if ( false === $parsed_url ) {
			return new WP_Error( 'Unable to parse endpoint URL' );
		}

		/**
		 * Filters the allowed URL schemes for this request.
		 *
		 * @param array<string>    $allowed_url_schemes The allowed URL schemes.
		 * @param HttpQueryContext $query_context       The current query context.
		 * @return array<string> The filtered allowed URL schemes.
		 */
		$allowed_url_schemes = apply_filters( 'remote_data_blocks_allowed_url_schemes', [ 'https' ], $this->query_context );

		if ( empty( $parsed_url['scheme'] ?? '' ) || ! in_array( $parsed_url['scheme'], $allowed_url_schemes, true ) ) {
			return new WP_Error( 'Invalid endpoint URL scheme' );
		}

		if ( empty( $parsed_url['host'] ?? '' ) ) {
			return new WP_Error( 'Invalid endpoint URL host' );
		}

		$scheme = $parsed_url['scheme'];
		$host = $parsed_url['host'];
		$user = $parsed_url['user'] ?? '';
		$path = $parsed_url['path'] ?? '';

		$query = ! empty( $parsed_url['query'] ?? '' ) ? '?' . $parsed_url['query'] : '';
		$port = ! empty( $parsed_url['port'] ?? '' ) ? ':' . $parsed_url['port'] : '';
		$pass = ! empty( $parsed_url['pass'] ?? '' ) ? ':' . $parsed_url['pass'] : '';
		$pass = ( $user || $pass ) ? $pass . '@' : '';

		$request_details = [
			'method' => $method,
			'options' => [
				RequestOptions::HEADERS => $headers,
				RequestOptions::JSON => $body,
			],
			'origin' => sprintf( '%s://%s%s%s%s', $scheme, $user, $pass, $host, $port ),
			'ttl' => $cache_ttl,
			'uri' => sprintf( '%s%s', $path, $query ),
		];

		/**
		 * Filters the request details before the HTTP request is dispatched.
		 *
		 * @param array<string, mixed> $request_details The request details.
		 * @param HttpQueryContext $query_context The query context.
		 * @param array<string, mixed> $input_variables The input variables for the current request.
		 * @return array<string, array{
		 *   method: string,
		 *   options: array<string, mixed>,
		 *   origin: string,
		 *   uri: string,
		 * }>
		 */
		return apply_filters( 'remote_data_blocks_request_details', $request_details, $this->query_context, $input_variables );
	}

	/**
	 * Dispatch the HTTP request and assemble the raw (pre-processed) response data.
	 *
	 * @param array<string, mixed> $input_variables The input variables for the current request.
	 * @return WP_Error|array{
	 *   metadata:      array<string, string|int|null>,
	 *   response_data: string|array|object|null,
	 * }
	 */
	protected function get_raw_response_data( array $input_variables ): array|WP_Error {
		$request_details = $this->get_request_details( $input_variables );

		if ( is_wp_error( $request_details ) ) {
			return $request_details;
		}

		$client_options = [
			HttpClient::CACHE_TTL_CLIENT_OPTION_KEY => $request_details['ttl'],
		];

		$this->http_client->init( $request_details['origin'], [], $client_options );

		try {
			$response = $this->http_client->request( $request_details['method'], $request_details['uri'], $request_details['options'] );
		} catch ( Exception $e ) {
			return new WP_Error( 'remote-data-blocks-unexpected-exception', $e->getMessage() );
		}

		$response_code = $response->getStatusCode();

		if ( $response_code < 200 || $response_code >= 300 ) {
			return new WP_Error( 'remote-data-blocks-bad-status-code', $response->getReasonPhrase() );
		}

		// The body is a stream... if we need to read it in chunks, etc. we can do so here.
		$raw_response_string = $response->getBody()->getContents();

		return [
			'metadata' => [
				'age' => intval( $response->getHeaderLine( 'Age' ) ),
				'status_code' => $response_code,
			],
			'response_data' => $raw_response_string,
		];
	}

	/**
	 * Get the response metadata for the query, which are available as bindings for
	 * field shortcodes.
	 *
	 * @param array $response_metadata The response metadata returned by the query runner.
	 * @param array $query_results     The results of the query.
	 * @return array array<string, array{
	 *   name:  string,
	 *   type:  string,
	 *   value: string|int|null,
	 * }>,
	 */
	protected function get_response_metadata( array $response_metadata, array $query_results ): array {
		$age = intval( $response_metadata['age'] ?? 0 );
		$time = time() - $age;

		$query_response_metadata = [
			'last_updated' => [
				'name' => 'Last updated',
				'type' => 'string',
				'value' => gmdate( 'Y-m-d H:i:s', $time ),
			],
			'total_count' => [
				'name' => 'Total count',
				'type' => 'integer',
				'value' => count( $query_results ),
			],
		];

		/**
		 * Filters the query response metadata, which are available as bindings for
		 * field shortcodes.
		 *
		 * @param array $query_response_metadata The query response metadata.
		 * @param HttpQueryContext $query_context The query context.
		 * @param array $response_metadata The response metadata returned by the query runner.
		 * @param array $query_results The results of the query.
		 * @return array The filtered query response metadata.
		 */
		return apply_filters( 'remote_data_blocks_query_response_metadata', $query_response_metadata, $this->query_context, $response_metadata, $query_results );
	}

	/**
	 * @inheritDoc
	 */
	public function execute( array $input_variables ): array|WP_Error {
		$raw_response_data = $this->get_raw_response_data( $input_variables );

		if ( is_wp_error( $raw_response_data ) ) {
			return $raw_response_data;
		}

		// Loose validation of the raw response data.
		if ( ! isset( $raw_response_data['metadata'], $raw_response_data['response_data'] ) || ! is_array( $raw_response_data['metadata'] ) ) {
			return new WP_Error( 'Invalid raw response data' );
		}

		$metadata = $raw_response_data['metadata'];
		$response_data = $raw_response_data['response_data'];

		// If the response data is a string, allow queries to implement their own
		// deserialization logic. Otherwise, JsonPath is prepared to work with a
		// string, array, object, or null.
		if ( is_string( $response_data ) ) {
			$response_data = $this->query_context->process_response( $response_data, $input_variables );
		}

		// Determine if the response data is expected to be a collection.
		$is_collection = $this->query_context->is_response_data_collection();

		// This method always returns an array, even if it's a single item. This
		// ensures a consistent response shape. The requestor is expected to inspect
		// is_collection and unwrap if necessary.
		$results = $this->map_fields( $response_data, $is_collection );

		return [
			'is_collection' => $is_collection,
			'metadata' => $this->get_response_metadata( $metadata, $results ),
			'results' => $results,
		];
	}

	/**
	 * Get the field value based on the field type. This method casts the field
	 * value to a string (since this will ultimately be used as block content).
	 *
	 * @param array|string $field_value   The field value.
	 * @param string       $default_value The default value.
	 * @param string       $field_type    The field type.
	 * @return string The field value.
	 */
	protected function get_field_value( array|string $field_value, string $default_value = '', string $field_type = 'string' ): string {
		$field_value_single = is_array( $field_value ) && count( $field_value ) > 1 ? $field_value : ( $field_value[0] ?? $default_value );

		switch ( $field_type ) {
			case 'base64':
				return base64_decode( $field_value_single );

			case 'html':
				return $field_value_single;

			case 'price':
				return sprintf( '$%s', number_format( (float) $field_value_single, 2 ) );

			case 'string':
				return wp_strip_all_tags( $field_value_single );
		}

		return (string) $field_value_single;
	}

	/**
	 * Map fields from the response data, adhering to the output schema defined by
	 * the query.
	 *
	 * @param string|array|object|null $response_data The response data to map. Can be JSON string, PHP associative array, PHP object, or null.
	 * @param bool                     $is_collection Whether the response data is a collection.
	 * @return null|array<int, array{
	 *   result: array{
	 *     name: string,
	 *     type: string,
	 *     value: string,
	 *   },
	 * }>
	 */
	protected function map_fields( string|array|object|null $response_data, bool $is_collection ): ?array {
		$root = $response_data;
		$output_schema = $this->query_context->output_schema;

		if ( ! empty( $output_schema['root_path'] ) ) {
			$json = new JsonObject( $root );
			$root = $json->get( $output_schema['root_path'] );
		} else {
			$root = $is_collection ? $root : [ $root ];
		}

		if ( empty( $root ) || empty( $output_schema['mappings'] ) ) {
			return $root;
		}

		// Loop over the returned items in the query result.
		return array_map( function ( $item ) use ( $output_schema ) {
			$json = new JsonObject( $item );

			// Loop over the output variables and extract the values from the item.
			$result = array_map( function ( $mapping ) use ( $json ) {
				if ( array_key_exists( 'generate', $mapping ) && is_callable( $mapping['generate'] ) ) {
					$field_value_single = $mapping['generate']( json_decode( $json->getJson(), true ) );
				} else {
					$field_path = $mapping['path'] ?? null;
					$field_value = $field_path ? $json->get( $field_path ) : '';

					// JSONPath always returns values in an array, even if there's only one value.
					// Because we're mostly interested in single values for field mapping, unwrap the array if it's only one item.
					$field_value_single = self::get_field_value( $field_value, $mapping['default_value'] ?? '', $mapping['type'] );
				}

				return array_merge( $mapping, [
					'value' => $field_value_single,
				] );
			}, $output_schema['mappings'] );

			// Nest result property to reserve additional meta in the future.
			return [
				'result' => $result,
			];
		}, $root );
	}
}
