# Query runner

A query runner executes a query and processes the results of a query. The default `QueryRunner` used by the [`HttpQuery` class](query.md) is designed to work with most APIs that transact over HTTP and return JSON, but you may want to provide a custom query runner if:

- Your API does not respond with JSON or requires custom deserialization logic.
- Your API uses a non-HTTP transport.
- You want to implement custom processing of the response data, which is not possible with the [provided filters](./hooks.md).

If your API transacts over HTTP and you want to customize the query runner, consider extending the `QueryRunner` class and providing an instance to your query via the `query_runner` option. If your API uses a non-HTTP transport or you want full control over query execution, you should implement your own query that implements `QueryInterface` and provides a custom `execute` method.
