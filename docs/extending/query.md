# Query

A query defines a request for data from a [data source](data-source.md). It defines input and output variables so that the Remote Data Blocks plugin knows how to interact with it. [Built-in services](data-source.md#built-in-services) offer automatic query registration.

## Code example

Here is an example of a query that fetches Zip code data:

```php
$zip_code_data_source = [
	'display_name' => 'Zip Code API',
	'endpoint' => 'https://api.zippopotam.us/us/',
];

$zip_code_query = [
	'data_source' => $zip_code_data_source,
	'display_name' => 'Get location by Zip code',
	// Provide a callable (closure) to dynamically generate the endpoint using
	// the base endpoint from the data source and the input variables.
	'endpoint' => function ( array $input_variables ) use ( $zip_code_data_source ): string {
		return $zip_code_data_source['endpoint'] . $input_variables['zip_code'];
	},
	'input_schema' => [
		'zip_code' => [
			'name' => 'Zip Code',
			'type' => 'string',
		],
	],
	'output_schema' => [
		'is_collection' => false, // This query returns a single record.
		'type' => [
			'zip_code' => [
				'name' => 'Zip Code',
				'path' => '$["post code"]', // JSON property with space requires brackets and quotes.
				'type' => 'string',
			],
			'city' => [
				'name' => 'City',
				'path' => '$.places[0]["place name"]', // JSON property with space requires brackets and quotes.
				'type' => 'string',
			],
			'state' => [
				'name' => 'State',
				'path' => '$.places[0].state',
				'type' => 'string',
			],
		],
	],
];
```

- The `endpoint` property is a callback function that constructs the query endpoint. In this case, the endpoint is constructed by appending the `zip_code` input variable to the data source endpoint.
- The `input_schema` property defines the input variables the query expects. For some queries, input variables might be used to construct a request body. In this case, the `zip_code` input variable is used to customize the query endpoint via the `endpoint` callback function.
- The `output_schema` property defines the output data that will be extracted from the API response and provided to the remote data block. The `path` property uses [JSONPath](https://jsonpath.com/) expressions to allow concise, no-code references to nested data.

This example features a small subset of the customization available for a query; see the full documentation below for details.

## Configuration

### display_name: string

The `display_name` property defines the query's human-friendly name.

### data_source: array|HttpDataSourceInterface (required)

The `data_source` property provides the [data source](./data-source.md) the query uses. It can be an array containing configuration (as in the example above) or an instance of a class that implements `HttpDataSourceInterface`.

### endpoint: string|callable

The `endpoint` property defines the query endpoint. It can be a string or a callable function that constructs the endpoint. The callable function accepts an associative array of input variables (`[ $var_name => $value ]`). If omitted, the query will use the endpoint defined by the data source.

#### Example

```php
'endpoint' => function( array $input_variables ) use ( $data_source ): string {
	return $data_source-['endpoint'] . $input_variables['zip_code'];
},
```

### input_schema: array

The `input_schema` property defines the input variables expected by the query, which can be used to formulate the endpoint, the request headers, or the request body. Further specification and examples are provided in the [`input_schema` documentation](./query-input-schema.md).

### output_schema: array (required)

The `output_schema` property defines how an API response should be transformed and provided to a remote data block. Further information and examples are provided in the [`output_schema` documentation](./query-output-schema.md).

### pagination_schema: array

If your query supports pagination, the `pagination_schema` property defines how to extract pagination-related values from the query response. If defined, the property should be an associative array with the following structure:

- `total_items`: A variable definition that extracts the total number of items across every page of results.
- `has_next_page`: A variable definition that extracts a boolean indicating whether there are more pages of results. Useful for APIs that do not report the total number of items.
- `cursor_next`: If your query supports cursor pagination, a variable definition that extracts the cursor for the next page of results. This output variable will also be mapped to `ui:pagination_cursor`, if present.
- `cursor_previous`: If your query supports cursor pagination, a variable definition that extracts the cursor for the previous page of results.

Note that one of `has_next_page` or `total_items` is required for all pagination types.

A pagination block will automatically be added to remote data blocks that support pagination.

#### Example

```php
'pagination_schema' => [
	'total_items' => [
		'name' => 'Total items',
		'path' => '$.pagination.totalItems',
		'type' => 'integer',
	],
	'cursor_next' => [
		'name' => 'Next page cursor',
		'path' => '$.pagination.nextCursor',
		'type' => 'string',
	],
	'cursor_previous' => [
		'name' => 'Previous page cursor',
		'path' => '$.pagination.previousCursor',
		'type' => 'string',
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

The `cache_ttl` property defines how long the query response should be cached in seconds. It can be an integer, a callable function that returns an integer, or `null`. The callable function accepts an associative array of input variables (`[ $var_name => $value ]`).

A value of `-1` indicates the query should not be cached. A value of `null` indicates the default TTL should be used (300 seconds). If omitted, the default TTL is used.

Remote data blocks utilize the WordPress object cache (`wp_cache_get()` / `wp_cache_set()`) for response caching. Ensure that your platform provides or installs a persistent object cache plugin so that this value is respected. If you do not have a peristent object cache, this property will be ignored and responses will only be cached in-memory. We do not recommend running the Remote Data Blocks plugin in this configuration.

Note that error responses are cached for 30 seconds to avoid overwhelming the remote data source with repeated requests under error conditions. Additionally, a small random jitter is added to the cache TTL to avoid cache stampedes.

#### Example

```php
'cache_ttl' => 3600, // Set the cache TTL to 1 hour
```

### image_url: string|null

The `image_url` property defines an image URL that represents the query in the UI. If omitted, the query will use the image URL defined by the data source.

### preprocess_response: callable

If you need to pre-process the response in some way before the output variables are extracted, provide a `preprocess_response` function. The function will receive the deserialized response.

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

By default, the query will use the default query runner, which works for almost every HTTP-powered API. Provide a custom query runner in the very rare cases where:

- Your API does not respond with JSON or requires custom deserialization logic.
- Your API uses a non-HTTP transport.
- You want to implement highly custom processing of the response data which is not possible with the [provided filters](hooks.md).

## GraphQL queries and mutations

This plugin provides `GraphqlQuery` and `GraphqlMutation` classes that makes it easier to work with GraphQL APIs.

```php
$graphql_query = [
	'__class' => 'RemoteDataBlocks\\Config\\Query\\GraphqlQuery',
	'data_source' => $graphql_data_source,
	'display_name' => 'Get a list of products',
	'graphql_query' => 'query GetProducts($first: Int) {
		products(first: $first) {
			nodes {
				id
				name
				price
			}
		}
	}',
	'input_schema' => [
		'first' => [
			'name' => 'First',
			'type' => 'integer',
			'default' => 10,
		],
	],
	'output_schema' => [
		'is_collection' => true,
		'path' => '$.data.products.nodes[*]',
		'type' => [
			'id' => [
				'name' => 'ID',
				'path' => '$.id',
				'type' => 'id',
			],
			'name' => [
				'name' => 'Name',
				'path' => '$.name',
				'type' => 'string',
			],
			'price' => [
				'name' => 'Price',
				'path' => '$.price',
				'type' => 'currency_in_current_locale',
			],
		],
	],
];
```

### GraphQL

The `GraphqlQuery` and `GraphqlMutation` classes extend the base query class, so they support all the properties defined above. Additionally, they have the following specific properties:

#### graphql_query: string

The `graphql_query` property defines the GraphQL query or mutation to execute. The variables should match the It should be a valid GraphQL query string, including any variables that the query expects.

#### request_method: string

The `request_method` property defines the HTTP request method used by the query or mutation. By default, it is `'POST'`.

### Next steps

Once you have defined your queries, you can use them to [register remote data blocks](block-registration.md).
