# Local Development

This repository includes tools for starting a local development environment using [`@wordpress/env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/), which requires Docker and Docker Compose. In addition, both `npm` and `composer` are required to install the local dependencies.

## Set up

Clone this repository and install its dependencies:.

```sh
npm install
```

To start a development environment with Xdebug enabled:

```sh
npm run dev
```

This will spin up a WordPress environment and a Valkey (Redis) instance for object cache. It will also build the block editor scripts, watch for changes, and open a Node.js debugging port. The WordPress environment will be available at `http://localhost:8888` (admin user: `admin`, password: `password`).

Stop the development environment with `Ctrl+C` and resume it by running the same command. You can also manually stop the environment with `npm run dev:stop`. Stopping the environment optionally stops the WordPress containers but preserves their state.

### Sharing configuration

Data sources configured via the Remote Data Blocks WordPress Admin UI are encrypted and stored as `remote_data_blocks_configs` in the Options table of the WordPress database.

If your local and production environments do not use the same encryption secrets, your configuration from one environment will not work in the other. Keep this in mind when migrating the database between environments.

### Testing

Run unit tests:

```sh
# all unit tests
npm run test

# only JavaScript unit tests
npm run test:js

# only PHP unit tests
npm run test:php

# only a specific test file
npm run test:js some/test/file.js
npm run test:php -- --filter SomeTestClass
```

For e2e tests, ensure the development environment is running, then execute:

```sh
npm run test:e2e
```

### Logs

Watch logs from the WordPress container:

```sh
npx wp-env logs
```

### WP-CLI

Run WP-CLI commands:

```sh
npm run wp-cli option get siteurl
```

### Destroy

Destroy your local environment and irreversibly delete all content, configuration, and data:

```sh
npm run dev:destroy
```

## Local playground

While not suitable for local developement, it can sometimes be useful to quickly spin up a local WordPress playground:

```sh
npm run build # or `npm start` in a separate terminal
npm run playground
```

Playgrounds do not closely mirror production environments and are missing persistent object cache, debugging tools, and other important features. Use `npm run dev` for local development.
