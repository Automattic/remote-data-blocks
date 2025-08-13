=== Remote Data Blocks ===
Contributors: chriszarate, maxschmeling, mhsdef and others.
Tested up to: 6.8
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Remote Data Blocks makes it easy to combine content and remote data in the block editor. Built-in caching ensures performance and reliability.

== Description ==

Remote Data Blocks is a WordPress plugin that makes it easy to combine content and remote data in the block editor. Easily register blocks that load data from Airtable, Google Sheets, Shopify, GitHub, or any other API. Your data stays in sync. Built-in caching ensures performance and reliability. <a href="https://github.com/Automattic/remote-data-blocks/blob/trunk/docs/concepts/index.md#supported-use-cases">Read more about well-supported use cases.</a>

<a href="https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Automattic/remote-data-blocks/trunk/blueprint.json">Launch in WordPress Playground</a>

<a href="https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Automattic/remote-data-blocks/trunk/blueprint.json">Launch the plugin in WordPress Playground</a> and explore. An example API ("Conference Event") is included, or visit Settings > Remote Data Blocks to add your own. Read our <a href="https://github.com/Automattic/remote-data-blocks/blob/trunk/docs/tutorials/index.md">tutorials</a> to dive in.

## Requirements
PHP 8.1+
WordPress 6.7+
A <a href="https://developer.wordpress.org/reference/classes/wp_object_cache/#persistent-cache-plugins">persistent object cache</a> is not strictly required, but it is highly recommended for optimal performance and to help avoid rate-limiting from remote data sources. If your WordPress environment does not provide persistent object cache, the plugin will utilize in-memory (per-request) caching.

## Installation
On WordPress VIP, you can install the plugin, and configure data sources in the Integration Center. Detailed instructions are available on the <a href="https://docs.wpvip.com/integrations/center/">WordPress VIP docs</a>.

For other WordPress environments, download the latest release of the plugin, unzip, and add it to the plugins/ directory of your WordPress site.

## License
Remote Data Blocks is licensed under the <a href="https://github.com/Automattic/remote-data-blocks/blob/trunk/LICENSE">GPLv2 (or later)</a>.

== External services ==

This plugin can connect to one of several API endpoints under your direction. It does not connect to these services if you do not attempt to add a data source.

It sends user provided inputs to retrieve Google Sheets data from the Google Workspace API.
This service is provided by Google Workspace: <a href="https://developers.google.com/workspace/terms">terms of use</a>, <a href="https://policies.google.com/privacy">privacy policy</a>.

It sends user provided inputs to retrieve Airtable data from the Airtable API.
This service is provided by Airtable: <a href="https://www.airtable.com/company/developer-terms">terms of use</a>, <a href="https://airtable.com/privacy">privacy policy</a>.

It sends user provided inputs to retrieve Shopify products from the Shopify GraphQL API.
This service is provided by Shopify: <a href="https://www.shopify.com/legal/api-terms">terms of use</a>, <a href="https://www.shopify.com/legal/privacy">privacy policy</a>.