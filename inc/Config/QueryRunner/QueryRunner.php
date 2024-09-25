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
 * @package remote-data-blocks
 * @since 0.1.0
 */
class QueryRunner implements QueryRunnerInterface {

	public function __construct(
		private HttpQueryContext $query_context,
		private HttpClient $http_client = new HttpClient()
	) {
	}

	protected function get_raw_response_data( array $input_variables ): array|WP_Error {
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
		$raw_response_string = $response->getBody()->getContents();

		return [
			'metadata'      => [
				'age'         => intval( $response->getHeaderLine( 'Age' ) ),
				'status_code' => $response_code,
			],
			'response_data' => $raw_response_string,
		];
	}

	/**
	 * Get the response metadata for the query.
	 *
	 * @param array $response_metadata The response metadata returned by the query runner.
	 * @param array $query_results The results of the query.
	 * @return array The response metadata.
	 */
	protected function get_response_metadata( array $response_metadata, array $query_results ): array {
		$age  = intval( $response_metadata['age'] ?? 0 );
		$time = time() - $age;

		$query_response_metadata = [
			'last_updated' => [
				'name'  => 'Last updated',
				'type'  => 'string',
				'value' => gmdate( 'Y-m-d H:i:s', $time ),
			],
			'total_count'  => [
				'name'  => 'Total count',
				'type'  => 'number',
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

	public function execute( array $input_variables ): array|WP_Error {
		$raw_response_data = $this->get_raw_response_data( $input_variables );

		if ( is_wp_error( $raw_response_data ) ) {
			return $raw_response_data;
		}

		// Loose validation of the raw response data.
		if ( ! isset( $raw_response_data['metadata'], $raw_response_data['response_data'] ) || ! is_array( $raw_response_data['metadata'] ) ) {
			return new WP_Error( 'Invalid raw response data' );
		}

		$metadata      = $raw_response_data['metadata'];
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
			'metadata'      => $this->get_response_metadata( $metadata, $results ),
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
				return sprintf( '$%s', number_format( (float) $field_value_single, 2 ) );

			case 'string':
				return wp_strip_all_tags( $field_value_single );
		}

		return (string) $field_value_single;
	}

	/**
	 * Map fields from the response data using the output variables defined by
	 * the query.
	 *
	 * @param string|array|object|null $response_data The response data to map. Can be JSON string, PHP associative array, PHP object, or null.
	 * @param bool $is_collection Whether the response data is a collection.
	 * @return array|null The mapped fields.
	 */
	private function map_fields( string|array|object|null $response_data, bool $is_collection ): ?array {
		$root             = $response_data;
		$output_variables = $this->query_context->output_variables;

		if ( ! empty( $output_variables['root_path'] ) ) {
			$json = new JsonObject( $root );
			$root = $json->get( $output_variables['root_path'] );
		} else {
			$root = $is_collection ? $root : [ $root ];
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
					$field_value_single = self::get_field_value( $field_value, $mapping['default_value'] ?? '', $mapping['type'] );
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
