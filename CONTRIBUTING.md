# Contributing

## Guidelines

- As with all WordPress projects, we want to ensure a welcoming environment for everyone. With that in mind, all contributors are expected to follow our [Code of Conduct](https://make.wordpress.org/handbook/community-code-of-conduct/).

- Contributors should review WordPress' [PHP coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/), WordPress' [JavaScript coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/) and [accessibility coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/accessibility/).

- Accessibility should be top of mind and thoroughly tested.

- You maintain copyright over any contribution you make. By submitting a pull request you agree to release that code under [Remote Data Blocks' License](LICENSE.md).

- When contributing to this repository, please first discuss the change you wish to make via issue, email, or any other method with the owners of this repository before making a change.

## Development Setup

For WP monolith:

```
npm install && npm run start:monolith
```

For WP backend + WP Components w/Next.js frontend, make sure the [wp-components repo](https://github.com/Automattic/wp-components) is at a peer level in your file system and then run from root here:

```
npm install && npm run start:decoupled
```

## Reporting Security Issues

Please see [SECURITY.md](SECURITY.md).
