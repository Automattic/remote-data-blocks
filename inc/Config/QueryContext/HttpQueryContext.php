<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\QueryContext;

use RemoteDataBlocks\Config\Datasource\HttpDatasource;
use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use RemoteDataBlocks\Config\QueryRunner\QueryRunnerInterface;

defined( 'ABSPATH' ) || exit();

/**
 * HttpQueryContext class
 *
 * Base class used to define a Remote Data Blocks Query. This class defines a
 * composable query that allows it to be composed with another query or a block.
 * 
 * @package remote-data-blocks
 * @since 0.1.0
 */
class HttpQueryContext implements QueryContextInterface, HttpQueryContextInterface {
	const VERSION = '0.1.0';

	/**
	 * Constructor.
	 *
	 * @param HttpDatasource $datasource The datasource that this query will use.
	 * @param array          $input_schema The input schema for this query.
	 * @param array          $output_schema The output schema for this query.
	 */
	public function __construct(
		private HttpDatasource $datasource,
		public array $input_schema = [],
		public array $output_schema = []
	) {
		// Provide input and output variables as public properties.
		$this->input_schema  = $this->get_input_schema();
		$this->output_schema = $this->get_output_schema();
	}

	/**
	 * Override this method to define the input fields accepted by this query. The
	 * return value of this function will be passed to several methods in this
	 * class (e.g., `get_endpoint`, `get_request_body`).
	 *
	 * @return array {
	 *   @type array $var_name {
	 *     @type string $default_value Optional default value of the variable.
	 *     @type string $name          Display name of the variable.
	 *     @type array  $overrides {
	 *       @type array {
	 *         @type $target Targeted override.
	 *         @type $type   Override type.
	 *       }
	 *     }
	 *     @type string $type         The variable type (string, number, boolean)
	 *   }
	 * }
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
	 */
	public function get_input_schema(): array {
		return $this->input_schema;
	}

	/**
	 * Override this method to define output fields produced by this query.
	 *
	 * @return array {
	 *   @type array $var_name {
	 *     @type string $default_value Optional default value of the variable.
	 *     @type string $name          Display name of the variable.
	 *     @type string $path          JSONPath expression to find the variable value.
	 *     @type string $type          The variable type (string, number, boolean)
	 *   }
	 * }
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
	 */
	public function get_output_schema(): array {
		return $this->output_schema;
	}

	/**
	 * Get the datasource associated with this query.
	 */
	public function get_datasource(): HttpDatasource {
		return $this->datasource;
	}

	/**
	 * Override this method to specify a custom endpoint for this query.
	 */
	public function get_endpoint( array $input_variables ): string {
		return $this->get_datasource()->get_endpoint();
	}

	/**
	 * Override this method to specify a custom image URL for this query that will
	 * represent it in the UI.
	 */
	public function get_image_url(): string|null {
		return $this->get_datasource()->get_image_url();
	}

	/**
	 * Override this method to define a request method for this query.
	 */
	public function get_request_method(): string {
		return 'GET';
	}

	/**
	 * Override this method to specify custom request headers for this query.
	 *
	 * @param array $input_variables The input variables for this query.
	 */
	public function get_request_headers( array $input_variables ): array {
		return $this->get_datasource()->get_request_headers();
	}

	/**
	 * Override this method to define a request body for this query. The input
	 * variables are provided as a $key => $value associative array.
	 *
	 * The result will be converted to JSON using `wp_json_encode`.
	 *
	 * @param array $input_variables The input variables for this query.
	 * @return array|null
	 */
	public function get_request_body( array $input_variables ): ?array {
		return null;
	}

	/**
	 * Override this method to specify a name that represents this query in the
	 * block editor.
	 */
	public function get_query_name(): string {
		return 'Query';
	}

	/**
	 * Override this method to specify a custom query runner for this query.
	 */
	public function get_query_runner(): QueryRunnerInterface {
		return new QueryRunner( $this );
	}

	/**
	 * Override this method to define the cache object TTL for this query. Return
	 * -1 to disable caching. Return null to use the default cache TTL.
	 *
	 * @return int|null The cache object TTL in seconds.
	 */
	public function get_cache_ttl( array $input_variables ): null|int {
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
	 * Override this method to process the raw response data from the query before
	 * it is passed to the query runner and the output variables are extracted. The
	 * result can be a JSON string, a PHP associative array, a PHP object, or null.
	 *
	 * @param string $raw_response_data The raw response data.
	 * @param array  $input_variables   The input variables for this query.
	 */
	public function process_response( string $raw_response_data, array $input_variables ): string|array|object|null {
		return $raw_response_data;
	}

	/**
	 * Authoritative truth of whether output is expected to be a collection.
	 */
	final public function is_response_data_collection(): bool {
		return $this->output_schema['is_collection'] ?? false;
	}
}
