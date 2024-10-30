<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\QueryContext;

use RemoteDataBlocks\Config\ArraySerializableInterface;
use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\DataSource\HttpDataSourceInterface;
use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use RemoteDataBlocks\Config\QueryRunner\QueryRunnerInterface;
use RemoteDataBlocks\Validation\Validator;
use RemoteDataBlocks\Validation\ValidatorInterface;

defined( 'ABSPATH' ) || exit();

/**
 * HttpQueryContext class
 *
 * Base class used to define a Remote Data Blocks Query. This class defines a
 * composable query that allows it to be composed with another query or a block.
 */
class HttpQueryContext implements QueryContextInterface, HttpQueryContextInterface, ArraySerializableInterface {
	protected const CONFIG_SCHEMA = [
		'type' => 'object',
		'properties' => [
			'input_schema' => [
				'type' => 'array',
				'items' => [
					'type' => 'object',
					'properties' => [
						'type' => [ 'type' => 'string' ],
						'name' => [ 'type' => 'string' ],
						'default_value' => [
							'type' => 'string',
							'required' => false,
						],
						'overrides' => [
							'type' => 'array',
							'required' => false,
						],
					],
				],
			],
			'output_schema' => [
				'type' => 'object',
				'properties' => [
					'root_path' => [
						'type' => 'string',
						'required' => false,
					],
					'is_collection' => [ 'type' => 'boolean' ],
					'mappings' => [
						'type' => 'array',
						'items' => [
							'type' => 'object',
							'properties' => [
								'name' => [ 'type' => 'string' ],
								'path' => [
									'type' => 'string',
									'required' => false,
								],
								'generate' => [
									'type' => 'function',
									'required' => false,
								],
								'type' => [ 'type' => 'string' ],
							],
						],
					],
				],
			],
			'query_name' => [
				'type' => 'string',
				'required' => false,
			],
		],
	];

	/**
	 * Constructor.
	 *
	 * @param HttpDataSource $data_source The data source that this query will use.
	 * @param array          $input_schema The input schema for this query.
	 * @param array          $output_schema The output schema for this query.
	 */
	public function __construct(
		private HttpDataSource $data_source,
		public array $input_schema = [],
		public array $output_schema = [],
		protected array $config = [],
	) {
		// Provide input and output variables as public properties.
		$this->input_schema = $this->get_input_schema();
		$this->output_schema = $this->get_output_schema();

		// @todo: expand or kill this
		$this->config = $config;
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
	 * Get the data source associated with this query.
	 */
	public function get_data_source(): HttpDataSource {
		return $this->data_source;
	}

	/**
	 * Override this method to specify a custom endpoint for this query.
	 */
	public function get_endpoint( array $input_variables ): string {
		return $this->get_data_source()->get_endpoint();
	}

	/**
	 * Override this method to specify a custom image URL for this query that will
	 * represent it in the UI.
	 */
	public function get_image_url(): string|null {
		return $this->get_data_source()->get_image_url();
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
		return $this->get_data_source()->get_request_headers();
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

	// @todo: consider splitting the data source injection out from query context so we don't have to tie a query
	// to a data source when instantiating. instead, we can just require applying queries to data sources in query
	// runner execution. ie: $query_runner->execute( $query, $data_source );
	//
	/** @psalm-suppress ParamNameMismatch reason: we want the clarity provided by the rename here */
	final public static function from_array( array $config, ?ValidatorInterface $validator = null ): static|\WP_Error {
		if ( ! isset( $config['data_source'] ) || ! $config['data_source'] instanceof HttpDataSourceInterface ) {
			return new \WP_Error( 'missing_data_source', __( 'Missing data source.', 'remote-data-blocks' ) );
		}

		$validator = $validator ?? new Validator( self::CONFIG_SCHEMA );
		$validated = $validator->validate( $config );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		return new static( $config['data_source'], $config['input_schema'], $config['output_schema'], $config );
	}

	public function to_array(): array {
		return $this->config;
	}
}
