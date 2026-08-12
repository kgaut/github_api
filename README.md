# Github API

A Drupal module that integrates your Drupal site with GitHub through the
[KnpLabs GitHub API](https://github.com/KnpLabs/php-github-api) client. It
provides a thin, injectable service around the client so you can list
repositories, read and create issues, and manage sub-issues from your own
Drupal code.

## Requirements

- Drupal `^8 || ^9 || ^10 || ^11`
- PHP `>= 7.4`
- [`knplabs/github-api`](https://github.com/KnpLabs/php-github-api) (installed
  automatically via Composer)

## Installation

Install the module with Composer so its dependencies are pulled in:

```bash
composer require kgaut/github_api
drush en github_api
```

## Configuration

1. Go to **Configuration → Web services → Github API settings**
   (`/admin/config/services/github_api`, route `github_api.settings_form`).
2. Generate a personal access token with the `repo` scope. The settings form
   provides a pre-filled link to GitHub's token creation page.
3. Paste the token into the **Token** field.
4. Optionally fill in **My GitHub username** — your GitHub login, used to
   auto-assign tickets (for example when creating TMA issues).
5. Save the configuration.

## Usage

The module exposes a `github_api.api` service (class
`Drupal\github_api\Api`). Inject it into your own services or load it from the
container:

```php
/** @var \Drupal\github_api\Api $api */
$api = \Drupal::service('github_api.api');

// List repositories accessible to the authenticated user.
$repositories = $api->listProjects();

// Read the issues of a repository.
$issues = $api->listIssues('kgaut', 'github_api');

// Show a single issue.
$issue = $api->showIssue('kgaut', 'github_api', 1);

// Create an issue, optionally assigning it to someone.
$created = $api->createIssue('kgaut', 'github_api', 'Bug title', 'Description', [
  $api->getMyUsername(),
]);

// Attach an existing issue as a sub-issue of a parent issue.
$api->addSubIssue('kgaut', 'github_api', $parentIssueNumber, $created['id']);
```

### Available methods

| Method                                   | Description                                            |
| ---------------------------------------- | ------------------------------------------------------ |
| `listProjects()`                         | Lists the repositories of the authenticated user.      |
| `showProject($project_id)`               | Returns information about a repository by its id.       |
| `listIssues($owner, $repo)`              | Lists all issues of a repository.                      |
| `showIssue($owner, $repo, $issue_id)`    | Returns a single issue.                                |
| `createIssue($owner, $repo, $title, ...)`| Creates an issue, with optional body and assignees.    |
| `updateIssue($owner, $repo, $n, $params)`| Updates an issue — e.g. `['state' => 'closed']`. Returns the updated issue. |
| `addAssignees($owner, $repo, $n, $users)`| Adds assignees. Additive: existing ones are kept.      |
| `removeAssignees($owner, $repo, $n, $users)`| Removes assignees. The others stay in place.        |
| `addSubIssue($owner, $repo, $parent, $id)`| Attaches an issue as a sub-issue of a parent issue.   |
| `listSubIssues($owner, $repo, $parent)`  | Lists the sub-issues of a parent issue.                |
| `listIssueComments($owner, $repo, $n)`   | Lists the comments of an issue, oldest first.          |
| `createIssueComment($owner, $repo, $n, $body)` | Posts a comment. ⚠️ Not idempotent.              |
| `listIssueTimeline($owner, $repo, $n)`   | Lists timeline events, including the commits that reference the issue. |
| `showCommit($owner, $repo, $sha)`        | Returns a single commit (message, author, URL).        |
| `getMyUsername()`                        | Returns the GitHub username configured in settings.    |

## License

GPL-2.0-or-later. See the `composer.json` file for details.
