# Query

A query defines a request for data from a [data source](data-source.md). It defines input and output variables so that the Remote Data Blocks plugin knows how to interact with it.

A common approach is to define a data source on the settings screen and then commit a custom query in code to fetch and process the data. The following example code does just that.

## HttpQuery

Most HTTP-powered APIs can be queried using an `HttpQuery`. Here's an example of a query for US ZIP code data. This examples assumes you have configured the data source in the UI, and have the UUID.

```php
if ( ! defined( 'REMOTE_DATA_BLOCKS_EXAMPLE_ZIP_CODE_DATA_SOURCE_UUID' ) ) {
	return;
}

$data_source = HttpDataSource::from_uuid( REMOTE_DATA_BLOCKS_EXAMPLE_ZIP_CODE_DATA_SOURCE_UUID );

if ( ! $data_source instanceof HttpDataSource ) {
	return;
}

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
- The `input_schema` property defines the input variables the query expects. For some queries, input variables might be used to construct a request body. In this case, the `zip_code` input variable is used to customize the query endpoint via the `endpoint` callback function.
- The `output_schema` property defines the output data that will be extracted from the API response. The `path` property uses [JSONPath](https://jsonpath.com/) expressions to allow concise, no-code references to nested data.

This example features a small subset of the customization available for a query; see the full documentation below for details.

## HttpQuery configuration

### display_name: string (required)

The `display_name` property defines the query's human-friendly name.

### data_source: HttpDataSourceInterface (required)

The `data_source` property provides the [data source](./data-source.md) the query uses.

### endpoint: string|callable

The `endpoint` property defines the query endpoint. It can be a string or a callable function that constructs the endpoint. The callable function accepts an associative array of input variables (`[ $var_name => $value ]`). If omitted, the query will use the endpoint defined by the data source.

#### Example

```php
'endpoint' => function( array $input_variables ) use ( $data_source ): string {
	return $data_source->get_endpoint() . $input_variables['zip_code'];
},
```

### input_schema: array

The `input_schema` property defines the input variables expected by the query. The method should return an associative array of input variable definitions. The keys of the array are machine-friendly input variable names, and the values are associative arrays with the following structure:

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
- `path` (optional): A [JSONPath](https://jsonpath.com/) expression to extract the variable value.
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
				return $response_data['places'][0]['place name'] . ', ' . $response_data['places'][0]['state'];
			},
			'type' => 'string',
		],
	],
],
```

#### More on `type`

The `type` property is where your data shape definition happens.

In the above Zip Code example the JSON response looks like this:

```json
{
	"post code": "17057",
	"country": "United States",
	"country abbreviation": "US",
	"places": [
		{
			"place name": "Middletown",
			"longitude": "-76.7331",
			"state": "Pennsylvania",
			"state abbreviation": "PA",
			"latitude": "40.2041"
		}
	]
}
```

You can see how the `type` property contains a nested output schema in itself. The `zip_code` array index starts a new definiton using `path` to find the specific value.

Where `city_state` uses the genrate function to combine two elements from inside the response. In this case we assume that the first returned place is accurate for the zip. This is a safe assumption for U.S. zip codes.

An example of collection JSON can be found in the [Chicago Institue of Art example](../../example/rest-api/art-institute/README.md). That API returns

```json
{
	"preference": null,
	"pagination": {
		"total": 183,
		"limit": 10,
		"offset": 0,
		"total_pages": 19,
		"current_page": 1
	},
	"data": [
		{
			"_score": 155.49371,
			"thumbnail": {
				"alt_text": "Color pastel drawing of ballerinas in tutus on stage, watched by audience.",
				"width": 3000,
				"lqip": "data:image/gif;base64,R0lGODlhCgAFAPUAADtMRVJPRFlOQlBNSFFNSEVURU1USldSS1dSTVRXTV9ZTldVUl1ZU2hbTVdkU19kVV5tX2FkUGFjVWVoVGhoVGZhW29lXGVtXG1rWmlpXW5tXmZxX3VxX1toZG5oYG5uZ3ZsY3BqZGN1a3RxYnFyZXRxZntxan19bnl9cnh7dX57doJ/dpGEeJKOhaCUjKebk6yflsGupQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAAAAAAALAAAAAAKAAUAAAYuQIjoQuGQTqhOyrEZYSQJA6AweURYrxIoxAhoMp9VywWLmRYqj6BxQFQshIEiCAA7",
				"height": 1502
			},
			"api_model": "artworks",
			"is_boosted": true,
			"api_link": "https://api.artic.edu/api/v1/artworks/61603",
			"id": 61603,
			"title": "Ballet at the Paris Opéra",
			"timestamp": "2025-01-14T22:26:21-06:00"
		},
		{
			"_score": 152.35487,
			"thumbnail": {
				"alt_text": "Impressionist painting of woman wearing green dress trying on hats.",
				"width": 5003,
				"lqip": "data:image/gif;base64,R0lGODlhBgAFAPQAAEMtIk40KE83KlhHLVxELlNPN1hLMVJOP19UN1dYM1lUOVpUP2dAIWlKKHZKKXZLKWRNPGpbMGpaNGtaOkxUTF9dRlJaS15YSV5kUnZpRH12W4ZkM49uRI52VQAAAAAAACH5BAAAAAAALAAAAAAGAAUAAAUY4AUtFWZxHZIdExFEybAJQGE00sNQmqOEADs=",
				"height": 4543
			},
			"api_model": "artworks",
			"is_boosted": true,
			"api_link": "https://api.artic.edu/api/v1/artworks/14572",
			"id": 14572,
			"title": "The Millinery Shop",
			"timestamp": "2025-01-14T23:26:12-06:00"
		}
	],
	"info": {
		"license_text": "The `description` field in this response is licensed under a Creative Commons Attribution 4.0 Generic License (CC-By) and the Terms and Conditions of artic.edu. All other data in this response is licensed under a Creative Commons Zero (CC0) 1.0 designation and the Terms and Conditions of artic.edu.",
		"license_links": [
			"https://creativecommons.org/publicdomain/zero/1.0/",
			"https://www.artic.edu/terms"
		],
		"version": "1.10"
	},
	"config": {
		"iiif_url": "https://www.artic.edu/iiif/2",
		"website_url": "http://www.artic.edu"
	}
}
```

And the output schema is defined as:

```php
'output_schema' => [
	'is_collection' => false,
	'path' => '$.data',
	'type' => [
		'id' => [
			'name' => 'Art ID',
			'type' => 'id',
		],
		'title' => [
			'name' => 'Title',
			'type' => 'string',
		],
		'image_id' => [
			'name' => 'Image ID',
			'type' => 'id',
		],
		'image_url' => [
			'name' => 'Image URL',
			'generate' => function ( $data ): string {
				return 'https://www.artic.edu/iiif/2/' . $data['image_id'] . '/full/843,/0/default.jpg';
			},
			'type' => 'image_url',
		],
	],
],
```

Here we can see at the top level a `path` variable is defined. From there the output variables used for each entry are named to match the property names in the JSON. This is a shortcut, you could

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

A value of `-1` indicates the query should not be cached. A value of `null` indicates the default TTL should be used (60 seconds). If omitted, the default TTL is used.

Remote data blocks utilize the WordPress object cache (`wp_cache_get()` / `wp_cache_set()`) for response caching. Ensure that your platform provides or installs a persistent object cache plugin so that this value is respected.

If you do not have a peristent object cache, no caching will be available. We do not recommend running the Remote Data Blocks plugin in this configuration.

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

Use the `query_runner` property to provide a custom [query runner](./query-runner.md) for the query. If omitted, the query will use the default query runner, which works well with most HTTP-powered APIs.
