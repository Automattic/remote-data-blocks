# Local Development

This repository includes tools for starting a development environment based on [`@wordpress/env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/), which requires Docker.

## Set up

Clone this repository and install Node.js and PHP dependencies:

```sh
npm install
composer install
```

To start a development environment with Xdebug enabled:

```sh
npm run start:monolith:xdebug
```

This will spin up a WordPress environment and a Valkey (Redis) instance for object cache. It will also build the block editor scripts, watch for changes, and open a Node.js debugging port. The WordPress environment will be available at `http://localhost:8888` (admin user: `admin`, password: `password`).

To start a decoupled / headless development environment, make sure the [wp-components repo](https://github.com/Automattic/wp-components) is at a peer level in your file system and then run from root:

```sh
npm run start:decoupled
```
