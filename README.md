---
### :warning: This plugin is currently in Beta. Breaking changes could occur with any update. Please test each release thoroughly before updating.
---

# Remote Data Blocks

**Remote Data Blocks** is a WordPress plugin that makes it easy to combine content and remote data in the block editor. Easily register blocks that load data from Airtable, Google Sheets, Shopify, GitHub, or any other API. Your data stays in sync. Built-in caching ensures performance and reliability.

Remote Data Blocks lets you take tabular data, stored elsewhere and display it as headings, paragraphs, images and buttons in WordPress. Either as a list of all the rows in your table or as a single entry. [Read more about well supported use cases](docs/concepts/index.md#supported-use-cases).

[![Launch in WordPress Playground](https://img.shields.io/badge/Launch%20in%20WordPress%20Playground-DA9A45?style=for-the-badge&logo=wordpress)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Automattic/remote-data-blocks/trunk/blueprint.json)

[Launch the plugin in WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Automattic/remote-data-blocks/trunk/blueprint.json) and explore. An example API ("Conference Event") is included, or visit Settings > Remote Data Blocks to add your own. Visit the [workflows guide](docs/workflows/index.md) to dive in.

## Installation

The latest version of the plugin is available in the default `trunk` branch of this repository.

### Install via `git subtree`

We recommend installing the latest plugin version [via `git subtree`][wpvip-plugin-subtrees] within your site's repository:

```bash
# Enter your project's root directory:
cd my-site-repo/

# Add a subtree for the trunk branch:
git subtree add --prefix plugins/remote-data-blocks git@github.com:Automattic/remote-data-blocks.git trunk --squash
```

To deploy the plugin to a remote branch, `git push` the committed subtree.

The `trunk` branch will stay up to date with the latest version of the plugin. Use this command to pull the latest `trunk` branch changes:

```bash
git subtree pull --prefix plugins/remote-data-blocks git@github.com:Automattic/remote-data-blocks.git trunk --squash
```

Ensure that the plugin is up-to-date by pulling changes often.

Note: We **do not recommend** using `git submodule`. [Submodules on WPVIP that require authentication][wpvip-plugin-submodules] will fail to deploy.

### Install via ZIP file

The latest version of the plugin can be downloaded from the [repository's Releases page](https://github.com/Automattic/remote-data-blocks/releases/latest/download/remote-data-blocks.zip). Unzip the downloaded plugin and add it to the `plugins/` directory of your site's GitHub repository.

### Plugin activation

We recommend [activating plugins with code][wpvip-plugin-activate].

## Requirements

- PHP 8.1+
- WordPress 6.7+

A [persistent object cache](https://developer.wordpress.org/reference/classes/wp_object_cache/#persistent-cache-plugins) is not strictly required, but it is highly recommended for optimal performance and to help avoid rate limiting from remote data sources. If your WordPress environment does not provide persistent object cache, the plugin will utilize in-memory (per-request) caching.

## Next steps

- Learn about the [core concepts](docs/concepts/index.md) behind Remote Data Blocks.
- Create your first connecting following our [tutorials](docs/tutorials/index.md) to see Remote Data Blocks in action.
- If you're a developer, you're ready to [extend Remote Data Blocks with custom code](docs/extending/index.md).
- Interested in contributing? Issues, pull requests, and discussions are welcome. Please see our [contribution guide](CONTRIBUTING.md) for more information.

## License

Remote Data Blocks is licensed under the [GPLv2 (or later)](LICENSE).

---

Made with ❤️ by [WordPress VIP](https://wpvip.com/).

[wpvip-plugin-activate]: https://docs.wpvip.com/how-tos/activate-plugins-through-code/
[wpvip-plugin-submodules]: https://docs.wpvip.com/technical-references/plugins/installing-plugins-best-practices/#h-submodules
[wpvip-plugin-subtrees]: https://docs.wpvip.com/technical-references/plugins/installing-plugins-best-practices/#h-subtrees
