# Remote Data Blocks

**Remote Data Blocks** is a WordPress plugin that makes it easy to combine content and remote data in the block editor. Easily register blocks that load data from Airtable, Google Sheets, Shopify, GitHub, or any other API. Your data stays in sync. Built-in caching ensures performance and reliability.

[![Launch in WordPress Playground](https://img.shields.io/badge/Launch%20in%20WordPress%20Playground-blue?style=for-the-badge)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Automattic/remote-data-blocks/trunk/blueprint.json)

[Launch the plugin in WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Automattic/remote-data-blocks/trunk/blueprint.json) and explore. An example API ("Conference Events") is included, or visit Settings > Remote Data Blocks to add your own. Visit the [workflows guide](docs/workflows/index.md) to dive in.

## Installation

[![Download Latest Release](https://img.shields.io/badge/Download%20Latest%20Release-blue?style=for-the-badge)](https://github.com/Automattic/remote-data-blocks/releases/latest/download/remote-data-blocks.zip)

### Requirements

- PHP 8.1+
- WordPress 6.7+

A [persistent object cache](https://developer.wordpress.org/reference/classes/wp_object_cache/#persistent-cache-plugins) is not strictly required, but it is highly recommended for optimal performance and to help avoid rate limiting from remote data sources. If your WordPress environment does not provide persistent object cache, the plugin will utilize in-memory (per-request) caching.

## Next steps

> [!WARNING]
> This plugin is under heavy active development and breaking changes may land without warning. If you are interested in evaluating or testing this plugin, please [open an issue](https://github.com/Automattic/remote-data-blocks/issues/new/choose) and we'll be in touch!

- Learn about the [core concepts](docs/concepts/index.md) behind Remote Data Blocks.
- Follow along with [example workflows](docs/workflows/index.md) to see Remote Data Blocks in action.
- If you're a developer, you're ready to [extend Remote Data Blocks with custom code](docs/extending/index.md).
- Interested in contributing? Issues, pull requests, and discussions are welcome. Please see our [contribution guide](CONTRIBUTING.md) for more information.

## License

Remote Data Blocks is licensed under the [GPLv2 (or later)](LICENSE).

---

Made with ❤️ by [WordPress VIP](https://wpvip.com/).
