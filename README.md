# Remote Data Blocks

**Remote Data Blocks** is a WordPress plugin that makes it easy to combine content and remote data in the block editor. Easily register blocks that load data from Airtable, Google Sheets, Shopify, GitHub, or any other API. Your data stays in sync. Built-in caching ensures performance and reliability. [Read more about well-supported use cases](docs/concepts/index.md#supported-use-cases).

[![Launch in WordPress Playground](https://img.shields.io/badge/Launch%20in%20WordPress%20Playground-black?style=for-the-badge&logo=wordpress)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Automattic/remote-data-blocks/trunk/blueprint.json)

[Launch the plugin in WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Automattic/remote-data-blocks/trunk/blueprint.json) and explore. An example API ("Conference Event") is included, or visit Settings > Remote Data Blocks to add your own. Read our [tutorials](docs/tutorials/index.md) to dive in.

## Next steps

- Learn about the [core concepts](docs/concepts/index.md) behind Remote Data Blocks.
- Follow our [tutorials](docs/tutorials/index.md) to create your first connection and see Remote Data Blocks in action.
- If you're a developer, you're ready to [extend Remote Data Blocks with custom code](docs/extending/index.md).
- Interested in contributing? Issues, pull requests, and discussions are welcome. Please see our [contribution guide](CONTRIBUTING.md) for more information.

## Requirements

- PHP 8.1+
- WordPress 6.7+

A [persistent object cache](https://developer.wordpress.org/reference/classes/wp_object_cache/#persistent-cache-plugins) is not strictly required, but it is highly recommended for optimal performance and to help avoid rate-limiting from remote data sources. If your WordPress environment does not provide persistent object cache, the plugin will utilize in-memory (per-request) caching.

## Installation

On WordPress VIP, you can install the plugin, and configure data sources in the Integration Center. Detailed instructions are available [on the WordPress VIP docs](https://docs.wpvip.com/integrations/center/).

For other WordPress environments, download [the latest release of the plugin](https://github.com/Automattic/remote-data-blocks/releases/latest/download/remote-data-blocks.zip), unzip, and add it to the `plugins/` directory of your WordPress site.

## License

Remote Data Blocks is licensed under the [GPLv2 (or later)](LICENSE).
