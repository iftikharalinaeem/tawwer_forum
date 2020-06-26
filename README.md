<img src="https://user-images.githubusercontent.com/1770056/51494323-414e8980-1d86-11e9-933c-e647b5ea49f4.png" alt="Vanilla Repo Logo" width=500/>

[![CircleCI](https://circleci.com/gh/vanilla/vanilla-cloud.svg?style=svg&circle-token=88ea96d98486f5168decf0f7fb19ff547a37962d)](https://app.circleci.com/pipelines/github/vanilla/vanilla-cloud)
[![codecov](https://codecov.io/gh/vanilla/vanilla-cloud/branch/master/graph/badge.svg?token=HPSZYSSN0A)](https://codecov.io/gh/vanilla/vanilla-cloud)

## Vanilla Cloud Repository

_Coming from the vanilla/vanilla, vanilla/internal, vanilla/multisite, or vanilla/knowledge repositories? See [this article on the migration](https://staff.vanillaforums.com/kb/articles/254-repository-differences-vanilla-vanilla-cloud)._

This repository is where primary development of Vanilla occurs. Vanilla staff developers should make PRs **here**, and not to [vanilla/vanilla](https://github.com/vanilla/vanilla).

See [Open Source and Licensing](#open-source-and-licensing) for more information about the [vanilla/vanilla](https://github.com/vanilla/vanilla) repository.

All proprietary cloud code should be maintained under the [`/cloud`](https://github.com/vanilla/vanilla-cloud/tree/master/cloud) directory. Everything outside of that directory should be licensed as `gpl-2.0-only` with a few exceptions maintained [here](#).

## Useful Links

-   [Public Documentation](https://success.vanillaforums.com/kb)
-   [Internal Documentation](https://staff.vanillaforums.com/kb)
-   Local Development - [Environment](https://github.com/vanilla/queue-stack-dev), [Configuration & Debugging](https://docs.vanillaforums.com/developer/tools/environment/) & [Build Tools](https://docs.vanillaforums.com/developer/tools/building-frontend/).
-   [Running with Vanilla Message Queue](https://github.com/vanilla/queue-stack-dev)
-   [Running Unit tests](https://github.com/vanilla/vanilla/blob/master/tests/README.md).
-   Coding Standard - [PHP](https://docs.vanillaforums.com/developer/contributing/coding-standard-php/), [Typescript](https://docs.vanillaforums.com/developer/contributing/coding-standard-typescript/), [Database Naming](https://docs.vanillaforums.com/developer/contributing/database-naming-standards/)
-   [Writing Pull Requests](https://docs.vanillaforums.com/developer/contributing/pull-requests/)
-   [Contributing Guidelines](https://github.com/vanilla/vanilla/blob/master/CONTRIBUTING.md)
-   [Contributing to Translations](https://github.com/vanilla/locales/blob/master/README.md)

## Releases & Branches

The `master` branch is considered a stable branch capable of being released at any time. Reviewed, stable changes land against `master` via pull-request.

Release branches match the following pattern `release/YYYY.XXX`. Example `release/2020.015` would indicate the 15th cloud release of 2020.

### Release Notes & Changelog

Vanilla cloud release notes are compiled here. https://success.vanillaforums.com/kb/docs

## Open Source and Licenses

The [vanilla/vanilla](https://github.com/vanilla/vanilla) repository is the place where code licensed as `gpl-2.0-only` is published. This process is currently manually synced, but will be automated in the future.

If you create a file outside of the [`/cloud`](https://github.com/vanilla/vanilla-cloud/tree/master/cloud) directory, **_it will be published to that repository eventually._**
