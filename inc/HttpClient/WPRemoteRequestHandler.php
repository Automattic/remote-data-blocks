<?php declare(strict_types = 1);

namespace RemoteDataBlocks\HttpClient;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Exception;
use function is_wp_error;
use function wp_remote_request;
use function wp_remote_retrieve_body;
use function wp_remote_retrieve_headers;
use function wp_remote_retrieve_response_code;

class WPRemoteRequestHandler {
	const DEFAULT_HTTP_VERSION = '1.1';
	const DEFAULT_TIMEOUT      = 5;

	public function __invoke( RequestInterface $request, array $options ): PromiseInterface {
		try {
			// Convert Guzzle request to arguments for wp_remote_request.
			$url  = (string) $request->getUri();
			$args = [
				'body'        => $request->getBody()->getContents(),
				'headers'     => [],
				'httpversion' => $options['httpversion'] ?? self::DEFAULT_HTTP_VERSION,
				'method'      => $request->getMethod(),
				'timeout'     => $options['timeout'] ?? self::DEFAULT_TIMEOUT,
			];

			// Collapse duplicate headers into a single comma-separated header.
			foreach ( $request->getHeaders() as $name => $values ) {
				$args['headers'][ $name ] = implode( ', ', $values );
			}

			// Make the request.
			$response = wp_remote_request( $url, $args );

			// Handle errors.
			if ( is_wp_error( $response ) ) {
				return new RejectedPromise( new Exception( $response->get_error_message() ) );
			}

			// Get the response data
			$response_code    = wp_remote_retrieve_response_code( $response );
			$response_body    = wp_remote_retrieve_body( $response );
			$response_headers = wp_remote_retrieve_headers( $response );

			// Create a Guzzle-compatible promise response.
			return Create::promiseFor(
				new Response(
					$response_code,
					$response_headers,
					$response_body
				)
			);
		} catch ( Exception $e ) {
			// Return rejected promise in case of an exception
			return new RejectedPromise( $e );
		}
	}
}
