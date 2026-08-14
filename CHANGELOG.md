# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `Api::listMilestones()`, `Api::listPullRequests()` and `Api::listReleases()`
  to list the milestones, pull requests and releases of a repository, all
  paginated through `ResultPager` like `listIssues()`.
  ⚠️ Their docblocks record **which of them can be ordered by update date, and
  which cannot** — the three endpoints do not behave alike, and the difference
  is invisible at call time:
  - **`listPullRequests()` honours** `['sort' => 'updated', 'direction' => 'desc']`;
  - **`listMilestones()` does not**, and silently pretends to. GitHub only
    accepts `sort=due_date|completeness` there, and
    `Github\Api\Issue\Milestones::all()` rewrites any other value to
    `due_date` before the request leaves the process. Measured against a
    24-milestone repository: `sort=updated` and a deliberately invalid
    `sort` value return a byte-identical list — which happens to be exactly
    `updated_at` descending, so the trap looks like a success. An
    incremental cursor built on it would skip milestones for good;
  - **`listReleases()` cannot support a cursor at all**: editing a release
    does not change its `created_at`, so a corrected old release never moves
    back to the top.
- `Api::createIssueComment()` to post a comment on an issue. ⚠️ Deliberately
  documented as **not idempotent**: calling it twice publishes two comments,
  visible to everyone and not undoable from the API — callers must never
  retry it automatically.
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
