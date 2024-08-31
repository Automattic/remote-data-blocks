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
		$response_data = $response->getBody()->getContents();

		$is_collection = $this->query_context->output_variables['is_collection'] ?? false;

		if ( isset( $response_data['errors'][0]['message'] ) ) {
			$logger = LoggerManager::instance();
			$logger->warning( sprintf( 'Query error: %s', esc_html( $response_data['errors'][0]['message'] ) ) );
		}

		$results = $this->query_context->get_results( $response_data, $input_variables );

		return [
			'is_collection' => $is_collection,
			'metadata'      => $this->query_context->get_metadata( $response, $results ),
			'results'       => $results,
		];
	}	
}
