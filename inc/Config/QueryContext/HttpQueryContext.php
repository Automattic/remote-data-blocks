<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\QueryContext;

use RemoteDataBlocks\Config\ArraySerializable;
use RemoteDataBlocks\Config\ArraySerializableInterface;
use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use RemoteDataBlocks\Validation\ConfigSchemas;
use WP_Error;

defined( 'ABSPATH' ) || exit();

/**
 * HttpQueryContext class
 *
 * Base class used to define a Remote Data Blocks Query. This class defines a
 * composable query that allows it to be composed with another query or a block.
 */
class HttpQueryContext extends ArraySerializable implements HttpQueryContextInterface, ArraySerializableInterface {
	/**
	 * Override this method to provide a custom execution implementation.
	 */
	public function execute( array $input_variables ): array|WP_Error {
		$query_runner = $this->config['query_runner'] ?? new QueryRunner();

		return $query_runner->execute( $this, $input_variables );
	}

	/**
	 * Override this method to define the cache object TTL for this query. Return
	 * -1 to disable caching. Return null to use the default cache TTL.
	 *
	 * @return int|null The cache object TTL in seconds.
	 */
	public function get_cache_ttl( array $input_variables ): null|int {
		if ( isset( $this->config['cache_ttl'] ) ) {
			return $this->get_or_call_from_config( 'cache_ttl', $input_variables );
		}

		// For most HTTP requests, we only want to cache GET requests. This is
		// overridden for GraphQL queries when using GraphqlQueryContext
		if ( 'GET' !== strtoupper( $this->get_request_method() ) ) {
			// Disable caching.
			return -1;
		}

		// Use default cache TTL.
		return null;
	}

	/**
	 * Get the data source associated with this query.
	 */
	public function get_data_source(): HttpDataSource {
		return $this->config['data_source'];
	}

	/**
	 * Override this method to specify a custom endpoint for this query.
	 */
	public function get_endpoint( array $input_variables ): string {
		return $this->get_or_call_from_config( 'endpoint', $input_variables ) ?? $this->get_data_source()->get_endpoint();
	}

	/**
	 * Override this method to specify a custom image URL for this query that will
	 * represent it in the UI.
	 */
	public function get_image_url(): string|null {
		return $this->config['image_url'] ?? $this->get_data_source()->get_image_url();
	}

	public function get_input_schema(): array {
		return $this->config['input_schema'] ?? [];
	}

	public function get_output_schema(): array {
		return $this->config['output_schema'];
	}

	/**
	 * Override this method to specify a name that represents this query in the
	 * block editor.
	 */
	public function get_query_key(): string {
		return $this->config['query_key'];
	}

	/**
	 * Override this method to specify a name that represents this query in the
	 * block editor.
	 */
	public function get_query_name(): string {
		return $this->config['query_name'] ?? $this->config['query_key'];
	}

	/**
	 * Override this method to define a request body for this query. A non-null
	 * result will be converted to JSON using `wp_json_encode`.
	 *
	 * @param array $input_variables The input variables for this query.
	 */
	public function get_request_body( array $input_variables ): ?array {
		return $this->get_or_call_from_config( 'request_body', $input_variables );
	}

	/**
	 * Override this method to specify custom request headers for this query.
	 *
	 * @param array $input_variables The input variables for this query.
	 */
	public function get_request_headers( array $input_variables ): array {
		return $this->get_or_call_from_config( 'request_headers', $input_variables ) ?? $this->get_data_source()->get_request_headers();
	}

	/**
	 * Override this method to define a request method for this query.
	 */
	public function get_request_method(): string {
		return $this->config['request_method'] ?? 'GET';
	}

	/**
	 * @inheritDoc
	 */
	protected static function get_config_schema(): array {
		return ConfigSchemas::get_http_query_config_schema();
	}

	/**
	 * Override this method to preprocess the response data before it is passed to
	 * the response parser.
	 *
	 * @param array $response_data The raw deserialized response data.
	 * @param array $input_variables The input variables for this query.
	 * @return array Preprocessed response data.
	 */
	public function preprocess_response( array $response_data, array $input_variables ): array {
		return $this->get_or_call_from_config( 'preprocess_response', $response_data, $input_variables ) ?? $response_data;
	}
}
