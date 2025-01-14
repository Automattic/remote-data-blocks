# Hooks

## Actions

### wpcomvip_log

If you want to send debugging information to another source besides [Query Monitor](../troubleshooting.md#query-monitor), use the `wpcomvip_log` action.

```php
function custom_log( string $namespace, string $level, string $message, array $context ): void {
    // Send the log to a custom destination.
}
add_action( 'wpcomvip_log', 'custom_log', 10, 4 );
```

## Filters

### wpcomvip_log_to_query_monitor

Filter whether to log a message to Query Monitor (default: `true`).

```php
add_filter( 'wpcomvip_log_to_query_monitor', '__return_false' );
```

### remote_data_blocks_register_example_block

Filter whether to register the included example API block ("Conference Event") (default: `true`).

```php
add_filter( 'remote_data_blocks_register_example_block', '__return_false' );
```

### remote_data_blocks_allowed_url_schemes

Filter the allowed URL schemes for this request. Only HTTPS is allowed by default, but it might be useful to relax this restriction in local environments.

```php
function custom_allowed_url_schemes( array $allowed_url_schemes, HttpQueryInterface $query ): array {
	// Modify the allowed URL schemes.
	return $allowed_url_schemes;
}
add_filter( 'remote_data_blocks_allowed_url_schemes', 'custom_allowed_url_schemes', 10, 2 );
```

### remote_data_blocks_request_details

Filter the request details (method, options, url) before the HTTP request is dispatched.

```php
function custom_request_details( array $request_details, HttpQueryInterface $query, array $input_variables ): array {
	// Modify the request details.
	return $request_details;
}
add_filter( 'remote_data_blocks_request_details', 'custom_request_details', 10, 3 );
```

### remote_data_blocks_query_input_variables

Filter the query input variables prior to query execution. This filter is useful for modifying the input variables for the current page-load, e.g., by pulling in data from query variables or other context. See [Overrides](overrides.md) for more information.

```php
add_filter( 'remote_data_blocks_query_input_variables', function ( array $input_variables, array $enabled_overrides, string $block_name, array $block_context ): array {
	if ( true === in_array( 'my_override', $enabled_overrides, true ) ) {
		$override_value = get_query_var( 'override_id' );

		if ( ! empty( $override_value ) ) {
			$input_variables['id'] = $override_value;
		}
	}

	return $input_variables;
}, 10, 4 );
```

Keep in mind that modifying query input variables will affect the object cache key used for query execution. This could result in a cache miss.

### remote_data_blocks_query_response

Filter the query response just after query execution. This filter is useful for modifying the query response for the current page-load, e.g., by pulling in data from query variables or other context. See [Overrides](overrides.md) for more information.

```php
add_filter( 'remote_data_blocks_query_response', function ( array $query_response, array $enabled_overrides, string $block_name, array $block_context ): array {
	if ( true === in_array( 'alternate_date_format', $enabled_overrides, true ) ) {
		$query_response['results'] = array_map( function ( array $result ) {
			$date = new DateTime( $result['date'] );
			$result['date'] = $date->format( 'Y F d' );
			return $result;
		}, $query_response['results'] );
	}

	return $input_variables;
}, 10, 4 );
```

The result of this filter is not cached, and will run for every block binding.

### remote_data_blocks_query_response_metadata

Filter the query response metadata, which are available as bindings for field shortcodes. In most cases, it is better to provide a custom query class and override the `get_response_metadata` method, but this filter is available in case that is not possible.

```php
function custom_query_response_metadata( array $metadata, HttpQueryInterface $query, array $input_variables ): array {
	// Modify the response metadata.
	return $metadata;
}
add_filter( 'remote_data_blocks_query_response_metadata', 'custom_query_response_metadata', 10, 3 );
```

### remote_data_blocks_bypass_cache

Filter to bypass the cache for a specific request (default: `false`).

```php
add_filter( 'remote_data_blocks_bypass_cache', '__return_true' );
```

### remote_data_blocks_http_client_retry_delay

### remote_data_blocks_http_client_retry_on_exception

### remote_data_blocks_http_client_retry_decider

Filter the HTTP retry logic when an HTTP request fails.
