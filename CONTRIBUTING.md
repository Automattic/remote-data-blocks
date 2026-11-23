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

Use the **Create release PR** GitHub Actions workflow on `trunk`. Choose a version
component (`major`, `minor`, `patch`, or `none`) and a release channel (`stable`,
`alpha`, or `beta`). Review and merge the generated `release/v<version>` PR into
`trunk` to publish the release.

The same process is available locally, with Node.js 24 and a clean worktree:

```sh
git switch trunk
git pull --ff-only
npm run release -- <major|minor|patch|none> [stable|alpha|beta]
git push --set-upstream origin release/v<version>
```

The channel defaults to `stable`, preserving the existing `./bin/release patch`
usage. Prerelease numbering starts at 1. For example, starting from `1.8.0`:

| Command                          | Result          |
| -------------------------------- | --------------- |
| `npm run release -- minor alpha` | `1.9.0-alpha.1` |
| `npm run release -- none alpha`  | `1.9.0-alpha.2` |
| `npm run release -- none beta`   | `1.9.0-beta.1`  |
| `npm run release -- none beta`   | `1.9.0-beta.2`  |
| `npm run release -- none stable` | `1.9.0`         |

`none` keeps the base `X.Y.Z`, either incrementing the current prerelease channel,
advancing from alpha to beta, or promoting to stable. Duplicate versions and
semantic downgrades (including stable to prerelease at the same base version) are
rejected. Choose a version bump to start a new prerelease cycle after a stable
release. The plugin header and version constant are updated together; the
WordPress.org `Stable tag` changes only for stable releases. RDB's private npm
manifest has no release version and is not modified.

Every release includes the production plugin ZIP on GitHub. Alpha/beta versions
are marked as prereleases and do not replace GitHub's latest stable release.
Only stable versions trigger WordPress.org deployment. The deploy workflow also
validates manual requests: the requested version must be stable and match the
checked-out plugin header, version constant, and readme stable tag.

Run the dependency-free release tests with:

```sh
node --test tools/release/*.test.mjs
```

The version policy and transition tests in `tools/release/version.mjs` and
`version.test.mjs` are ported from
[VIP Workflows](https://github.com/Automattic/vip-workflows/tree/3436ad7fb7be7fbb0a03445fe4dc5cfa22bd1093/tools/release).
The version policy is independent of plugin paths, packaging, and deployment so
it can be extracted without changing release semantics. The shell entrypoint
and deploy validation are RDB-specific adapters.
