# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `Api::listIssueComments()` to list the comments of an issue (paginated,
  oldest first).
- `Api::listSubIssues()` to list the sub-issues of a parent issue — the
  method was called by consumers but missing from the API wrapper.

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

## [1.0.x]

### Added

- Initial release of the Github API module.
- `github_api.api` service wrapping the KnpLabs GitHub API client with token
  authentication.
- `Api::listProjects()` to list the authenticated user's repositories.
- `Api::showProject()` to fetch a repository by its id.
- `Api::listIssues()` and `Api::showIssue()` to read repository issues.
- Settings form to configure the GitHub personal access token.
