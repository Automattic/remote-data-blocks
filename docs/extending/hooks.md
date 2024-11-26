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

Filter the allowed URL schemes for this request. By default, only HTTPS is allowed, but it might be useful to relax this restriction in local environments.

```php
function custom_allowed_url_schemes( array $allowed_url_schemes, HttpQueryContext $query_context ): array {
	// Modify the allowed URL schemes.
	return $allowed_url_schemes;
}
add_filter( 'remote_data_blocks_allowed_url_schemes', 'custom_allowed_url_schemes', 10, 2 );
```

### remote_data_blocks_request_details

Filter the request details (method, options, url) before the HTTP request is dispatched.

```php
function custom_request_details( array $request_details, HttpQueryContext $query_context, array $input_variables ): array {
	// Modify the request details.
	return $request_details;
}
add_filter( 'remote_data_blocks_request_details', 'custom_request_details', 10, 3 );
```

### remote_data_blocks_query_response_metadata

Filter the query response metadata, which are available as bindings for field shortcodes. In most cases, it is better to provide a custom query class and override the `get_response_metadata` method but this filter is available in case that is not possible.

```php
function custom_query_response_metadata( array $metadata, HttpQueryContext $query_context, array $input_variables ): array {
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
