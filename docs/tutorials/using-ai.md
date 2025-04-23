# Using AI to build a custom integration

This tutorial will walk you through using AI to build a custom integration. We've added suggested AI prompts throughout the documentation, but this tutorial will guide you start to finish. The tutorial assumes you are using an IDE with AI support.

## 1. Intial prompt

This initial prompt provides the context for the project and is desigend to attempt to one-shot the plugin build. This example uses the [PokeAPI](https://pokeapi.co/) to fetch Pokemon data. The more well documented the API, the better the intial result.

```text
Imagine you are a senior WordPress developer that is using the Remote Data Blocks plugin to display Pokeman from the PokeAPI: https://pokeapi.co/docs/v2 in WordPress. 

Create a plugin that requires the Remote Data Blocks plugin, if it isn't installed already, install it. For all your coding tasks follow WordPress PHP coding standards found here: https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/

Before you do anything, carefully review the Remote Data Blocks documentation: https://raw.githubusercontent.com/Automattic/remote-data-blocks/refs/heads/trunk/docs/ai.md. When this documentation and general WordPress documentation conflict, the Remote Blocks documentation is correct. 

Here is an example of a custom HTTP data source: https://raw.githubusercontent.com/Automattic/remote-data-blocks/refs/heads/trunk/example/rest-api/art-institute/art-institute.php Use this example as a template for the plugin.

The plugin should:

1. Create a custom HTTP data source for the PokeAPI
2. Register a remote data block to display a list of Pokemon
3. Register a remote data block to display a single Pokemon

Once the initial code is written double check that everything matches the types specified in the Remote Data Blocks documentation. If you find any errors, fix them.
```



