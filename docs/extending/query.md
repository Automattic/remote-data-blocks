# Query

A query defines a request for data from a [data source](data-source.md) and makes that data available to a remote data block. A query defines input and output variables so that the Remote Data Blocks plugin knows how to interact with it.

## HttpQueryContext

Most HTTP-powered APIs can be queried by defining a class that extends `HttpQueryContext`. Here's an example of a query for US ZIP code data:

```php
class GetZipCodeQuery extends HttpQueryContext {
	public function get_input_schema(): array {
		return [
			'zip_code' => [
				'name' => 'Zip Code',
				'type' => 'string',
			],
		];
	}

	public function get_output_schema(): array {
		return [
			'is_collection' => false,
			'mappings'      => [
				'zip_code' => [
					'name' => 'Zip Code',
					'path' => '$["post code"]',
					'type' => 'string',
				],
				'city'     => [
					'name' => 'City',
					'path' => '$.places[0]["place name"]',
					'type' => 'string',
				],
				'state'    => [
					'name' => 'State',
					'path' => '$.places[0].state',
					'type' => 'string',
				],
			],
		];
	}

	public function get_endpoint( $input_variables ): string {
		return $this->get_data_source()->get_endpoint() . $input_variables['zip_code'];
	}
}
```

The `get_input_schema` method defines the input data expected by the query. For some queries, input variables might be used to construct a request body, but in this case the `zip_code` input variable is used to customize the query endpoint via the `get_endpoint()` method.

The `get_output_schema` method defines how to extract data from the API response. The `path` property uses [JSONPath](https://jsonpath.com/) expressions to allow concise, no-code references to nested data.

This example features a snall subset of the customization available for a query; see the full documentation below for details.

## HttpQueryContext documentation

### VERSION

The `VERSION` constant defines the current semver of `HttpQueryContext`. It is currently ignored but in the future may be used to navigate breaking changes.

### get_input_schema(): array

The `get_input_schema` method defines the input data expected by the query. The method should return an associative array of input variable definitions. The keys of the array are machine-friendly input variable names and the values are associative arrays with the following structure:

- `name` (optional): The human-friendly display name of the input variable
- `default_value` (optional): The default value for the input variable.
- `overrides` (optional): An array of possible [overrides](overrides.md) for the input variable. Each override is an associative array with the following keys:
  - `type`: The type of the override. Supported values are `query_var` and `url`.
  - `target`: The targeted entity for the override (e.g., the query or URL variable that contains the overridde).
- `type` (required): The type of the input variable. Supported types are:
  - `number`
  - `string`
  - `id`

#### Example

```php
public function get_input_schema(): array {
	return [
		'zip_code' => [
			'name' => 'Zip Code',
			'type' => 'string',
		],
	];
}
```

The default implementation returns an empty array.

### get_output_schema(): array

The `get_output_schema` method defines how to extract data from the API response. The method should return an associative array with the following structure:

- `is_collection` (optional, default `false`): A boolean indicating whether the response data is a collection. If false, only a single item will be returned.
- `mappings` (required): An associative array of output variable definitions. The keys of the array are machine-friendly output variable names and the values are associative arrays with the following structure:
  - `name` (optional): The human-friendly display name of the output variable.
  - `default_value` (optional): The default value for the output variable.
  - `path` (required): A [JSONPath](https://jsonpath.com/) expression to extract the variable value.
  - `type` (required): The type of the output variable. Supported types are -
    - `id`
    - `base64`
    - `boolean`
    - `number`
    - `string`
    - `button_url`
    - `image_url`
    - `image_alt`
    - `currency`
    - `markdown`

#### Example

```php
public function get_output_schema(): array {
	return [
		'is_collection' => false,
		'mappings'      => [
			'zip_code' => [
				'name' => 'Zip Code',
				'path' => '$["post code"]',
				'type' => 'string',
			],
			'city'     => [
				'name' => 'City',
				'path' => '$.places[0]["place name"]',
				'type' => 'string',
			],
			'state'    => [
				'name' => 'State',
				'path' => '$.places[0].state',
				'type' => 'string',
			],
		],
	];
}
```

The default implementation returns an empty array.

### get_data_source(): DataSourceInterface

The `get_data_source` method returns the data source associated with the query. By default, this method returns the data source that was provided to the class constructor. In most instances, you should not need to override this method.

### get_endpoint( array $input_variables ): string

By default, the `get_endpoint` method proxies to the `get_endpoint` method of query's data source. Override this method to set a custom endpoint for the query—for example, to construct the endpoints using an input variable. The input variables for the current request are provided as an associative array (`[ $var_name => $value ]`).

#### Example

```php
public function get_endpoint( $input_variables ): string {
	return $this->get_data_source()->get_endpoint() . $input_variables['zip_code'];
}
```

### get_image_url(): string|null

By default, the `get_image_url` method proxies to the `get_image_url` method of the query's data source. Override this method to provide an image URL that will represent the query in the UI.

### get_request_method(): string

By default, `get_request_method` returns `'GET'`. Override this method if your query uses a different HTTP request method.

### get_request_headers( array $input_variables ): array

By default, the `get_request_headers` method proxies to the `get_request_headers` method of the query's data source. Override this method to provide custom request headers for the query. The input variables for the current request are provided as an associative array (`[ $var_name => $value ]`).

### Example

```php
public function get_request_headers( array $input_variables ): array {
	return array_merge(
		$this->get_data_source()->get_request_headers(),
		[ 'X-Product-ID' => $input_variables['product_id'] ]
	);
}
```

### get_request_body( array $input_variables ): array|null

Override this method to define a request body for this query. The return value will be converted to JSON using `wp_json_encode`. The input variables for the current request are provided as an associative array (`[ $var_name => $value ]`).

### get_query_name(): string

Override this method to specify a name that represents the query in UI.

### get_query_runner(): QueryRunnerInterface

Override this method to specify a custom [query runner](query-runner.md) for this query. The default query runner works well with most HTTP-powered APIs.

### process_response( string $raw_response_data, array $input_variables ): string|array|object|null

The default query runner assumes a JSON response and decodes it. If you need to implement custom deserialization or want to process the response in some way before the output variables are extracted, override this method. The mappings and JSONPath expressions defined by `get_output_schema` will be applied to the return value of this method.

## QueryContextInterface

The `QueryContextInterface` interface defines the methods that must be implemented by a query class. If you have highly custom requirements that cannot be met by `HttpQueryContext`, you can implement `QueryContextInterface` directly.
