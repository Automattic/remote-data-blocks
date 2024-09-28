# Local Development

This repository includes tools for starting a local development environment using [`@wordpress/env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/), which requires Docker and Docker Compose.

## Set up

Clone this repository and install Node.js and PHP dependencies:

```sh
npm install
composer install
```

To start a development environment with Xdebug enabled:

```sh
npm run start:monolith-xdebug
```

This will spin up a WordPress environment and a Valkey (Redis) instance for object cache. It will also build the block editor scripts, watch for changes, and open a Node.js debugging port. The WordPress environment will be available at `http://localhost:8888` (admin user: `admin`, password: `password`).

Stop the development environment with `Ctrl+C` and resume it by running the same command. You can also manually stop the environment with `npm run stop`. Stopping the environment stops the WordPress containers but preserves their state.

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
npm run destroy
```

## Local playground

While not a suitable for local developement, it can sometimes be useful to quickly spin up a local WordPress playground using `@wp-now/wp-now`:

```sh
npm run build # or `npm run start` in a separate terminal
npm run start:playground
```

Playgrounds do not closely mirror production environments and are missing persistent object cache, debugging tools, and other important features. Use `npm run start:monolith-xdebug` for local development.
