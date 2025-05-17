# Query `output_schema` property

A query's `output_schema` defines how an API response should be transformed and provided to a remote data block. A typical goal is to transform the API response into a flat array of fields that can be bound to blocks, while omitting values that are not needed. Output can be nested, but nested values cannot be bound to blocks.

Note that the output schema may require updates whenever the shape or schema of the API response changes. Similarly, changing the slug or `type` of a field may break existing bindings. Consider creating a new query and remote data block if you need to make breaking changes to an output schema.

## Properties

- `format` (optional): A callable function that formats the output variable value.
- `generate` (optional): A callable function that generates or extracts the output variable value from the response, as an alternative to `path`.
- `is_collection` (optional, default `false`): A boolean indicating whether the response data is a collection. If false, only a single item will be returned.
- `name` (optional): The human-friendly display name of the output variable.
- `default_value` (optional): The default value for the output variable.
- `path` (optional): A [JSONPath](https://jsonpath.com/) expression to extract the variable value from the response. Note that path expressions are relative to the current item and its type; path expressions therefore "build" on each other when you nest types.
- `type` (required): A primitive type (e.g., `string`, `boolean`) or a nested output schema.

Accepted primitive types are:

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

## Single entity example

Using the [Zip Code example](https://github.com/Automattic/remote-data-blocks/blob/trunk/example/rest-api/zip-code/README.md), the JSON response returned by the API looks like this:

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

And the corresponding `output_schema` definition might look like this:

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
			'generate' => function( array $data ): string|null {
				if ( empty( $data['places'] ) ) {
					return null;
				}

				return $data['places'][0]['place name'] . ', ' . $data['places'][0]['state abbreviation'];
			},
			'type' => 'string',
		],
	],
],
```

- The `is_collection` property indicates whether the output represents a single entity or a collection of entities. In this case, it is set to `false` because the API returns a single entity.
- The `type` property at the root level begins the type definition. The `zip_code` and `city_state` array keys are "slugs" that identify the field. The array values define types that describe how to extract a value for those fields.
- The `zip_code` field is extracted via a [JSONPath](http://jsonpath.com) expression defined in the `path` property.
- The `city_state` field provides a callable via the `generate` property. That function receives the data and combines two elements to form the value.
- A `default_value` property provides a value that will be used if the provided `path` expression or `generate` function resolve to a null value.

The result of applying this output schema to the example JSON response is:

```php
[
	zip_code => '17057',
	city_state => 'Middletown, PA',
]
```

## Collection example

An example of collection JSON can be found in the [Chicago Institue of Art example](https://github.com/Automattic/remote-data-blocks/blob/trunk/example/rest-api/art-institute/README.md). That API returns (in part):

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

An output schema can be defined as:

```php
'output_schema' => [
	'is_collection' => true,
	'path' => '$.data[*]',
	'type' => [
		'id' => [
			'name' => 'Art ID',
			'type' => 'id',
		],
		'title' => [
			'name' => 'Art Title',
			'type' => 'string',
		],
	],
],
```

- The `is_collection` property is set to `true` to indicate that the output represents a collection of entities.
- A top-level `path` expression (`$.data[*]`) indicates that the collection is contained in the `data` property of the response.
- The `type` property defines two fields: `id` and `title`.
  - Note that the nested type definitions do not provide a `path` expression. When omitted, the plugin will use the slug as the expected path. This is a shorthand for the following output schema with explicit `path` expressions:

```php
'output_schema' => [
	'is_collection' => true,
	'path' => '$.data[*]',
	'type' => [
		'id' => [
			'name' => 'Art ID',
			'path' => '$.id',
			'type' => 'id',
		],
		'title' => [
			'name' => 'Art Title',
			'path' => '$.title',
			'type' => 'string',
		],
	],
],
```

We can enhance the output schema with additional fields and options:

```php
'output_schema' => [
	'is_collection' => true,
	'path' => '$.data[*]',
	'type' => [
		'id' => [
			'name' => 'Art ID',
			'type' => 'id',
		],
		'title' => [
			'name' => 'Art Title',
			'format' => function ( string $value ): string {
				return ucfirst( $value );
			},
			'type' => 'string',
		],
		'thumbnail_image_alt' => [
			'name' => 'Thumbnail alt text',
			'path' => '$.thumbnail.alt_text',
			'type' => 'image_alt',
		],
		'thumbnail_image_url' => [
			'name' => 'Thumbnail',
            'path' => '$.thumbnail.lqip',
			'type' => 'image_url',
		],
	],
],
```

The `format` property allows you to define a callable that will be applied to the value before it is returned.

Applying this output schema to the response JSON would result in the following output:

```php
[
	[
		'id' => 61603,
		'title' => 'Ballet at the Paris Opéra',
		'thumbnail_image_alt' => 'Color pastel drawing of ballerinas in tutus on stage, watched by audience.',
		'thumbnail_image_url' => 'data:image/gif;base64,R0lGODlhCgAFAPUAADtMRVJPRFlOQlBNSFFNSEVURU1USldSS1dSTVRXTV9ZTldVUl1ZU2hbTVdkU19kVV5tX2FkUGFjVWVoVGhoVGZhW29lXGVtXG1rWmlpXW5tXmZxX3VxX1toZG5oYG5uZ3ZsY3BqZGN1a3RxYnFyZXRxZntxan19bnl9cnh7dX57doJ/dpGEeJKOhaCUjKebk6yflsGupQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAAAAAAALAAAAAAKAAUAAAYuQIjoQuGQTqhOyrEZYSQJA6AweURYrxIoxAhoMp9VywWLmRYqj6BxQFQshIEiCAA7',
	],
	[
		'id' => 14572,
		'title' => 'The Millinery Shop',
		'thumbnail_image_alt' => 'Impressionist painting of woman wearing green dress trying on hats.',
		'thumbnail_image_url' => 'data:image/gif;base64,R0lGODlhBgAFAPQAAEMtIk40KE83KlhHLVxELlNPN1hLMVJOP19UN1dYM1lUOVpUP2dAIWlKKHZKKXZLKWRNPGpbMGpaNGtaOkxUTF9dRlJaS15YSV5kUnZpRH12W4ZkM49uRI52VQAAAAAAACH5BAAAAAAALAAAAAAGAAUAAAUY4AUtFWZxHZIdExFEybAJQGE00sNQmqOEADs=',
	],
]
```
