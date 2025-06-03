# Extending

> [!TIP]
> Make sure you've read the [core concepts](../concepts/index.md) behind Remote Data Blocks before extending the plugin.

Data sources and queries can be configured in the plugin UI but, sometimes, you need to write code to implement custom functionality or connect with data sources that aren't fully supported. Remote Data Blocks provides flexible configuration, extendable classes, hooks, and filters to help you connect to any remote data source and customize the output.

## Customization

Defining a data source or query in code gives you complete control over how data is fetched, processed, and rendered. In the case of unsupported APIs, it's a necessary step to define the schema and logic for fetching data.

- [Data source](data-source.md)
- [Query](query.md)
- [Block registration](block-registration.md)

## Advanced customization

- [Block patterns](block-patterns.md)
- [Hooks (actions and filters)](hooks.md)
- [Overrides](overrides.md)

## Examples and AI prompts

The included [examples](https://github.com/Automattic/remote-data-blocks/blob/trunk/example/README.md) provide detailed code samples and templates.

For quick development, we highly recommend [leveraging AI](ai-prompts.md) to scaffold and iterate on new integrations.

## Local development environment

This repository includes tools for quickly starting a [local development environment](../local-development.md).

## Data Flow

Here's a short overview of how data flows through the plugin when a post with a remote data block is rendered:

1. WordPress core loads the post content, parses the blocks, and recognizes that a paragraph block has a [block binding](../concepts/block-bindings.md).
2. WordPress core calls the block binding callback function: `BlockBindings::get_value()`.
3. The callback function inspects the paragraph block. Using the block context supplied by the parent remote data block, it determines which [query](query.md) to execute.
4. The query is executed: `$query->execute()`.
5. Various properties of the query are requested by the query runner, including the endpoint, request headers, request method, and request body. Some of these properties are delegated to the data source (`$query->get_data_source()`).
6. The query is dispatched, and the response data is inspected, formatted into a consistent shape, and returned to the block binding callback function.
7. The callback function extracts the requested field from the response data and returns it to WordPress core for rendering.
