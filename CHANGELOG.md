# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
This project does not publish tagged releases, so entries below are grouped by
the date of the changes as recorded in the Git history.

## [Unreleased] - 2026-07-02

### Added

- `Api::createIssue()` to create an issue in a repository, with an optional
  body and list of assignees.
- `Api::addSubIssue()` to attach an existing issue as a sub-issue of a parent
  issue via GitHub's sub-issues endpoint.
- `Api::getMyUsername()` to read the configured GitHub username.
- **My GitHub username** setting in the configuration form, used to
  auto-assign tickets.
- `README.md` documenting installation, configuration, and usage of the
  `github_api.api` service.
- This changelog.
- GitHub Actions workflow running PHPCS (`Drupal`, `DrupalPractice`) and
  PHPStan (`phpstan-drupal`), with `phpcs.xml.dist` and `phpstan.neon`
  configuration.

### Changed

- Documented every `Api` method with Drupal-style docblocks and guarded the
  API calls that can return a string so they always return an array.

### Fixed

- Corrected the module description in `composer.json` (it still referred to
  GitLab, a leftover from the module this one was scaffolded from).

## [2025-01-30]

### Added

- Drupal 11 compatibility (`core_version_requirement: ^8 || ^9 || ^10 || ^11`).

## [2024-08-21]

### Changed

- Switched token authentication to `Github\AuthMethod::ACCESS_TOKEN`, replacing
  the removed `Client::AUTH_ACCESS_TOKEN` constant (KnpLabs github-api v3+).
- Removed the `php-http/guzzle6-adapter` dependency from `composer.json`.

## [2023-11-20]

### Added

- Drupal 10 compatibility (`core_version_requirement: ^8 || ^9 || ^10`).

### Changed

- Loosened the `php-http/guzzle6-adapter` version constraint (2023-11-06).

## [2020-11-25]

### Added

- `Api::showIssue()` to return a single issue.

## [2020-11-23]

### Added

- Initial release of the Github API module (scaffolding, routing, permissions
  and menu link).
- Settings form to configure the GitHub personal access token.
- `github_api.api` service authenticating against the KnpLabs GitHub API client
  with an access token.
- `Api::listProjects()` to list the authenticated user's repositories.
- `Api::showProject()` to fetch a repository by its id.
- `Api::listIssues()` to list a repository's issues, with pagination.
