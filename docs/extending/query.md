# Query

A query defines a request for data from a [data source](data-source.md). It defines input and output variables so that the Remote Data Blocks plugin knows how to interact with it.

## HttpQuery

Most HTTP-powered APIs can be queried using an `HttpQuery`. Here's an example of a query for US ZIP code data:

```php
$data_source = HttpDataSource::from_array( [
	'service_config' => [
		'__version' => 1,
		'display_name' => 'Zip Code API',
		'endpoint' => 'https://api.zippopotam.us/us/',
	],
] );

$query = HttpQuery::from_array( [
	'display_name' => 'Get location by Zip code',
	'data_source' => $data_source,
	'endpoint' => function( array $input_variables ) use ( $data_source ): string {
		return $data_source->get_endpoint() . $input_variables['zip_code'];
	},
	'input_schema' => [
		'zip_code' => [
			'name' => 'Zip Code',
			'type' => 'string',
		],
	],
	'output_schema' => [
		'is_collection' => false,
		'type' => [
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
	],
] );
```

- The `endpoint` property is a callback function that constructs the query endpoint. In this case, the endpoint is constructed by appending the `zip_code` input variable to the data source endpoint.
- The `input_schema` property defines the input variables expected by the query. For some queries, input variables might be used to construct a request body, but in this case the `zip_code` input variable is used to customize the query endpoint via the `endpoint` callback function.
- The `output_schema` property defines the output data that will be extracted from the API response. The `path` property uses [JSONPath](https://jsonpath.com/) expressions to allow concise, no-code references to nested data.

This example features a small subset of the customization available for a query; see the full documentation below for details.

## HttpQuery configuration

### display_name: string (required)

The `display_name` property defines the human-friendly name of the query.

### data_source: HttpDataSourceInterface (required)

The `data_source` property provides the [data source](./data-source.md) used by the query.

### endpoint: string|callable

The `endpoint` property defines the query endpoint. It can be a string or a callable function that constructs the endpoint. The callable function accepts an associative array of input variables (`[ $var_name => $value ]`). If omitted, the query will use the endpoint defined by the data source.

#### Example

```php
'endpoint' => function( array $input_variables ) use ( $data_source ): string {
	return $data_source->get_endpoint() . $input_variables['zip_code'];
},
```

### input_schema: array

The `input_schema` property defines the input variables expected by the query. The method should return an associative array of input variable definitions. The keys of the array are machine-friendly input variable names and the values are associative arrays with the following structure:

- `name` (optional): The human-friendly display name of the input variable
- `default_value` (optional): The default value for the input variable.
- `type` (required): The primitive type of the input variable. Supported types are:
  - `boolean`
  - `id`
  - `integer`
  - `null`
  - `number`
  - `string`

#### Example

```php
'input_schema' => [
	'zip_code' => [
		'name' => 'Zip Code',
		'type' => 'string',
	],
],
```

If omitted, it defaults to an empty array.

### output_schema: array (required)

The `output_schema` property defines how to extract data from the API response. The method should return an associative array with the following structure:

- `format` (optional): A callable function that formats the output variable value.
- `generate` (optional): A callable function that generates or extracts the output variable value from the response, as an alternative to `path`.
- `is_collection` (optional, default `false`): A boolean indicating whether the response data is a collection. If false, only a single item will be returned.
- `name` (optional): The human-friendly display name of the output variable.
- `default_value` (optional): The default value for the output variable.
- `path` (required): A [JSONPath](https://jsonpath.com/) expression to extract the variable value.
- `type` (required): A primitive type (e.g., `string`, `boolean`) or a nested output schema. Accepted primitive types are:
  - `boolean`
  - `button_url`
  - `email_address`
  - `html`
  - `id`
  - `image_alt`
  - `image_url`
  - `integer`
  - `markdown`
  - `null`
  - `number`
  - `string`
  - `url`
  - `uuid`

#### Example

```php
'output_schema' => [
	'is_collection' => false,
	'type' => [
		'zip_code' => [
			'name' => 'Zip Code',
			'path' => '$["post code"]',
			'type' => 'string',
		],
		'city_state' => [
			'name' => 'City, State',
			'default_value' => 'Unknown',
			'generate' => function( array $response_data ): string {
				return $response_data[0]['place name'] . ', ' . $response_data[0]['state'];
			},
			'type' => 'string',
		],
	],
],
```

### request_method: string

The `request_method` property defines the HTTP request method used by the query. By default, it is `'GET'`.

### request_headers: array|callable

The `request_headers` property defines the request headers for the query. It can be an associative array or a callable function that returns an associative array. The callable function accepts an associative array of input variables (`[ $var_name => $value ]`). If omitted, the query will use the request headers defined by the data source.

### Example

```php
'request_headers' => function( array $input_variables ) use ( $data_source ): array {
	return array_merge(
		$data_source->get_request_headers(),
		[ 'X-Foo' => $input_variables['foo'] ]
	);
},
```

### request_body: array|callable

The `request_body` property defines the request body for the query. It can be an associative array or a callable function that returns an associative array. The callable function accepts an associative array of input variables (`[ $var_name => $value ]`). If omitted, the query will not have a request body.

### cache_ttl: int|null|callable

The `cache_ttl` property defines how long the query response should be cached, in seconds. It can be an integer, a callable function that returns an integer, or `null`. The callable function accepts an associative array of input variables (`[ $var_name => $value ]`).

A value of `-1` indicates the query should not be cached. A value of `null` indicates the default TTL should be used (60 seconds). If omitted, the default TTL is used.

### image_url: string|null

The `image_url` property defines an image URL that represents the query in the UI. If omitted, the query will use the image URL defined by the data source.

### preprocess_response: callable

If you need to prerocess the response in some way before the output variables are extracted, provide a `preprocess_response` function. The function will receive the deserialized response.

#### Example

```php
'preprocess_response' => function( mixed $response_data, array $input_variables ): array {
	$some_computed_property = compute_property( $response_data['foo']['bar'] ?? '' );

	return array_merge(
		$response_data,
		[ 'computed_property' => $some_computed_property ]
	);
},
```

### query_runner: QueryRunnerInterface

Use the `query_runner` property to provide a custom [query runner](./query-runner.md) for the query. If omitted, the query will use the default query runner, which works well with most HTTP-powered APIs.
