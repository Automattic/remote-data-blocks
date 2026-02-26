# AGENTS.md — Remote Data Blocks

A WordPress plugin that integrates external data sources into the block editor. It registers custom blocks that fetch data from APIs (Airtable, Google Sheets, Shopify, and others) with built-in caching. Requires PHP 8.1+ and WordPress 6.7+.

## Development

The local dev environment uses `wp-env`. Start it with `npm run dev` and stop with `npm run dev:stop`. The plugin can also be run in WordPress Playground via `npm run playground`.

## Codebase

**PHP (`inc/`)** — Plugin backend, PSR-4 autoloaded under the `RemoteDataBlocks\` namespace.

- `Editor/` — Block registration, block bindings, data binding, pattern editor
- `HttpClient/` — HTTP layer (Guzzle orchestration delegated to `wp_remote_request`)
- `Integrations/` — Data source connectors (Airtable, Google Sheets, Shopify, VIP Block Data API)
- `REST/` — REST API endpoints (`remote-data-blocks/v1`)
- `Config/` and `Store/` — Data source configuration and storage
- `PluginSettings/` — Admin settings page
- `Validation/`, `Sanitization/`, `Formatting/` — Input handling

**TypeScript (`src/`)** — Editor UI and block client code, built with `wp-scripts`.

- `blocks/` and `block-editor/` — Custom block implementations and editor enhancements
- `data-sources/` and `config/` — Data source management UI
- `settings/` — Plugin settings page UI
- `store/` — WordPress data store (`@wordpress/data`)

**Tests (`tests/`)** — Mirrors the source structure: `tests/inc/` for PHP unit tests, `tests/src/` for JS tests, `tests/e2e/` for Playwright, `tests/integration/` for integration tests.

**Examples (`example/`)** — Sample block implementations for reference.
