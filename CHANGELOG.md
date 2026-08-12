# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `Api::updateIssue()` to update an issue, typically to close or reopen it
  (`['state' => 'closed'|'open']`). Returns the updated issue payload, so
  callers do not need a second request to observe the new state.
- `Api::addAssignees()` and `Api::removeAssignees()` to assign and unassign
  users. Both are additive/subtractive rather than a wholesale replacement:
  assignees that are not listed stay in place, so self-assigning never
  silently drops someone else's assignment.
- `Api::listIssueTimeline()` to list the timeline events of an issue,
  including the `referenced` and `cross-referenced` events that record which
  commits mention it. Calls `configure()` explicitly, since
  `Issue::timeline()` returns a bare instance and the endpoint expects the
  mockingbird preview `Accept` header.
- `Api::showCommit()` to fetch a single commit (message, author, URL).

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
