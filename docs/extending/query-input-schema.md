# Query `input_schema` property

The `input_schema` property defines the input variables expected by the query. The property should be an associative array of input variable definitions. The keys of the array are machine-friendly input variable names, and the values are associative arrays with the following structure:

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

There are also some special input variable types:

- `ui:search_input`: A variable with this type indicates that the query supports searching. It must accept a `string` containing search terms.
- `ui:pagination_offset`: A variable with this type indicates that the query supports offset pagination. It must accept an `integer` containing the requested offset. See `pagination_schema` for additional information and requirements.
- `ui:pagination_page`: A variable with this type indicates that the query supports page-based pagination. It must accept an `integer` containing the requested results page. See `pagination_schema` for additional information and requirements.
- `ui:pagination_per_page`: A variable with this type indicates that the query supports controlling the number of resultsper page. It must accept an `integer` containing the number of requested results.
- `ui:pagination_cursor_next` and `ui_pagination_cursor_previous`: Variables with these types indicate that the query supports cursor pagination. They accept `string`s containing the requested cursor. See `pagination_schema` for additional information and requirements.
- `ui:pagination_cursor`: A variable with this type indicates support for a simple variant of cursor pagination that uses a single cursor instead of a pair of forward / backward cursors. It accepts a `string` containing the requested cursor. See `pagination_schema` for additional information and requirements.

#### Example with search and pagination input variables

```php
'input_schema' => [
	'search' => [
		'name' => 'Search terms',
		'type' => 'ui:search_input',
	],
	'limit' => [
		'default_value' => 10,
		'name' => 'Pagination limit',
		'type' => 'ui:pagination_per_page',
	],
	'page' => [
		'default_value' => 1,
		'name' => 'Pagination page',
		'type' => 'ui:pagination_page',
	],
],
```

If omitted, `input_schema` defaults to an empty array.
