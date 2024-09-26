# Query runner

A query runner executes a query and processes the results. The default `QueryRunner` used by the [`HttpQueryContext` class](query.md#HttpQueryContext) is designed to work with most APIs that transact over HTTP and return JSON, but you may want to provide a custom query runner if:

- Your API does not respond with JSON or requires custom deserialization logic.
- Your API uses a non-HTTP transport.
- You want to implement custom processing of the response data that is not possible with the provided filters.

## QueryRunner

If your API transacts over HTTP and you want to customize the query runner, consider extending the `QueryRunner` class and overriding select methods.

### execute( array $input_variables ): array|WP_Error

The `execute` method executes the query and returns the parsed data. The input variables for the current request are provided as an associative array (`[ $var_name => $value ]`).

### get_request_details( array $input_variables ): array|WP_Error

The `get_request_details` method extracts and validates the request details provided by the query. The input variables for the current request are provided as an associative array (`[ $var_name => $value ]`). The return value is an associative array that provides the HTTP method, request options, origin, and URI.

### get_raw_response_data( array $input_variables ): array|WP_Error

The `get_raw_response_data` method dispatches the HTTP request and assembles the raw (pre-processed) response data. The input variables for the current request are provided as an associative array (`[ $var_name => $value ]`). The return value is an associative array that provides the response metadata and the raw response data.

### get_response_metadata( array $response_metadata, array $query_results ): array

The `get_response_metadata` method returns the response metadata for the query, which are available as bindings for [field shortcodes](field-shortcodes.md).

### map_fields( string|array|object|null $response_data, bool $is_collection ): ?array

The `map_fields` method maps fields from the API response data, adhering to the output schema defined by the query.

### get_field_value( array|string $field_value, string $default_value = '', string $field_type = 'string' ): string

The `get_field_value` method computes the field value based on the field type. Overriding this method can be useful if you have custom field types and want to format the value in a specific way (e.g., a custom date format).

## QueryRunnerInterface

If you want to implement a query runner from scratch, `QueryRunnerInterface` requires only a single method, `execute`:

### execute( array $input_variables ): array

The `execute` method executes the query and returns the parsed data. The input variables for the current request are provided as an associative array (`[ $var_name => $value ]`).
