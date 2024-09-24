<?php

declare(strict_types = 1);

/**
 * HttpQueryContext class
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */

namespace RemoteDataBlocks\Config\QueryContext;

use RemoteDataBlocks\Config\Datasource\HttpDatasource;
use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use RemoteDataBlocks\Config\QueryRunner\QueryRunnerInterface;

defined( 'ABSPATH' ) || exit();

/**
 * Base class used to define a Remote Data Blocks Query. This class defines a
 * composable query that allows it to be composed with another query or a block.
 */
class HttpQueryContext implements QueryContextInterface, HttpQueryContextInterface {
	const VERSION = '0.1.0';

	/**
	 * A definition of input fields accepted by this query. These values of these
	 * input fields will be passed to the `get_request_body` method.
	 *
	 * @var array {
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
	 */
	public array $input_variables = [];

	/**
	 * A definition of output fields produced by this query.
	 *
	 * @var array {
	 *   @type array $var_name {
	 *     @type string $default_value Optional default value of the variable.
	 *     @type string $name          Display name of the variable.
	 *     @type string $path          JSONPath expression to find the variable value.
	 *     @type string $type          The variable type (string, number, boolean)
	 *   }
	 * }
	 */
	public array $output_variables = [];

	/**
	 * Constructor.
	 *
	 * @param HttpDatasource $datasource The datasource that this query will use.
	 */
	public function __construct( private HttpDatasource $datasource ) {
	}

	/**
	 * Get the datasource associated with this query.
	 */
	public function get_datasource(): HttpDatasource {
		return $this->datasource;
	}

	/**
	 * Override this method to specify a custom endpoint for this query.
	 *
	 * @return string
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
	 * @return array
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
	 * Override this method to process the raw response data from the query before
	 * it is passed to the query runner and the output variables are extracted. The
	 * result can be a JSON string, a PHP associative array, a PHP object, or null.
	 *
	 * @param string $raw_response_data The raw response data.
	 * @param array  $input_variables   The input variables for this query.
	 * @return string|array|object|null
	 */
	public function process_response( string $raw_response_data, array $input_variables ): string|array|object|null {
		return $raw_response_data;
	}

	/**
	 * Authoritative truth of whether output is expected to be a collection.
	 *
	 * @return bool
	 */
	final public function is_response_data_collection(): bool {
		return $this->output_variables['is_collection'] ?? false;
	}
}
