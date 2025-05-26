# Contributing

## Guidelines

- All contributors are expected to follow our [Code of Conduct](https://make.wordpress.org/handbook/community-code-of-conduct/), to ensure a welcoming environment for everyone.

- Contributors should review the WordPress [PHP coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/), [JavaScript coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/), and [accessibility coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/accessibility/). Accessibility in particular should be top of mind and thoroughly tested.

- You maintain copyright over any contribution you make. By submitting a pull request, you agree to release that code under [our license](https://github.com/Automattic/remote-data-blocks/blob/trunk/LICENSE).

- Before opening a pull request, please first discuss the change you wish to make via an issue or discussion.

## Reporting security issues

Please see [SECURITY.md](SECURITY.md).

## Development environment

Please see our guide to setting up a [local development environment](docs/local-development.md).

## Versioning

Remote Data Blocks uses [semantic versioning](https://semver.org/).

## Release process

1. Checkout the `trunk` branch and ensure it is up to date.
2. Run the release script: `./bin/release <major|minor|patch>`
3. Push the new release branch to the remote repository and create a pull request.
4. Merge the pull request into `trunk`.

A new release will be automatically published on GitHub via GitHub Actions.
