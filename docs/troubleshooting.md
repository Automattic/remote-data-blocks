# Troubleshooting and debugging

## Query monitor

Installing the [Query Monitor plugin](https://wordpress.org/plugins/query-monitor/) in your local development environment is highly recommended. When present, Remote Data Blocks will output debugging information to the Query Monitor "Logs" panel, including error details, stack traces, query execution details, and cache hit/miss status.

## Debugging

The [local development environment](local-development.md) includes Xdebug for debugging PHP code and a Node.js debugging port for debugging block editor scripts.

## Resetting config

If you need to reset the Remote Data Blocks configuration in your local development environment, you can use WP-CLI to delete the configuration option. This will permananently delete all configuration values, including access tokens and API keys.

```sh
npm run wp-cli option delete remote_data_blocks_config
```
