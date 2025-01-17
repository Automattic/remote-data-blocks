# Example: "GitHub Markdown File" block

This example registers a remote data block that renders a Markdown file from a GitHub repository. This is more than just a theoretical example: We use this code on [remotedatablocks.com](https://remotedatablocks.com) to render the plugin documentation from [Markdown files in this repo](https://github.com/Automattic/remote-data-blocks/tree/trunk/docs)!

## Custom query runner

This plugin expects APIs to return JSON, but we instruct GitHub's API to return HTML (converted from Markdown). To handle this, we provide a custom [query runner](../../../docs/extending/query-runner.md) that wraps the HTML in an object structure.

## Markdown link resolution

The syntax for linking between Markdown files is different from the syntax for linking between HTML pages, so we use a `generate` function in the query's output schema to transform the HTML output and update the links. See `markdown-links.php` if you are interested in how that works.
