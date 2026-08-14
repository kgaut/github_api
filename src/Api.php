<?php

namespace Drupal\github_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Github\AuthMethod;
use Github\Client;
use Github\HttpClient\Message\ResponseMediator;
use Github\ResultPager;

class Api {

  protected ImmutableConfig $config;

  protected Client $client;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('github_api.settings');
  }

  protected function init() {
    if (!isset($this->client)) {
      $this->client = new Client();
      $result = $this->client->authenticate($this->config->get('token'), NULL, AuthMethod::ACCESS_TOKEN);
    }
  }

  /**
   * Returns all projects on the server
   *
   * @param bool $simple
   * @param bool $includeArchived
   * @param array $additionalParams
   *
   * @return array
   * @throws \Http\Client\Exception
   */
  public function listProjects(bool $simple = TRUE, $includeArchived = FALSE, $additionalParams = []) : array {
    $this->init();
    $paginator  = new ResultPager($this->client);
    $parameters = [];

    return $paginator->fetchAll($this->client->api('user'), 'myRepositories', $parameters);
  }

  /**
   * Returns information about a given project.
   *
   * @param $project_id
   *
   * @return array
   */
  public function showProject($project_id) : array {
    $this->init();
    return $this->client->repository()->showById($project_id);
  }

  public function listIssues(string $username, string $repository, $additionalParams = []): array {
    $this->init();
    $paginator = new ResultPager($this->client);
    $parameters = [$username, $repository, $additionalParams];
    return $paginator->fetchAll($this->client->api('issues'), 'all', $parameters);
  }

  public function showIssue(string $username, string $repository, int $issue_id): array {
    $this->init();
    return $this->client->issue()->show($username, $repository, $issue_id);
  }

  public function getMyUsername(): string {
    return (string) ($this->config->get('my_username') ?? '');
  }

  public function createIssue(string $username, string $repository, string $title, string $body = '', array $assignees = []): array {
    $this->init();
    $params = ['title' => $title, 'body' => $body];
    if ($assignees !== []) {
      $params['assignees'] = $assignees;
    }
    return $this->client->issue()->create($username, $repository, $params);
  }

  /**
   * Attaches an issue as a sub-issue of a parent issue.
   *
   * @param string $username
   *   Repository owner.
   * @param string $repository
   *   Repository name.
   * @param int $parentIssueNumber
   *   Number of the parent issue (repo-scoped issue number).
   * @param int $subIssueId
   *   Global id of the child issue (the "id" field, not its number).
   *
   * @return array
   *   The parent issue payload returned by GitHub.
   */
  public function addSubIssue(string $username, string $repository, int $parentIssueNumber, int $subIssueId): array {
    $this->init();
    $response = $this->client->getHttpClient()->post(
      sprintf('/repos/%s/%s/issues/%d/sub_issues', $username, $repository, $parentIssueNumber),
      ['Content-Type' => 'application/json'],
      json_encode(['sub_issue_id' => $subIssueId], JSON_THROW_ON_ERROR),
    );
    return ResponseMediator::getContent($response);
  }

  /**
   * Lists the sub-issues of a parent issue.
   *
   * @param string $username
   *   Repository owner.
   * @param string $repository
   *   Repository name.
   * @param int $parentIssueNumber
   *   Number of the parent issue (repo-scoped issue number).
   *
   * @return array
   *   Raw sub-issue payloads.
   */
  public function listSubIssues(string $username, string $repository, int $parentIssueNumber): array {
    $this->init();
    $response = $this->client->getHttpClient()->get(
      sprintf('/repos/%s/%s/issues/%d/sub_issues?per_page=100', $username, $repository, $parentIssueNumber),
    );
    return ResponseMediator::getContent($response);
  }

  /**
   * Lists the comments of an issue, oldest first.
   *
   * @param string $username
   *   Repository owner.
   * @param string $repository
   *   Repository name.
   * @param int $issueNumber
   *   Repo-scoped issue number.
   *
   * @return array
   *   Raw GitHub comment payloads (id, user, body, created_at, updated_at,
   *   html_url…).
   */
  public function listIssueComments(string $username, string $repository, int $issueNumber): array {
    $this->init();
    $paginator = new ResultPager($this->client);
    return $paginator->fetchAll($this->client->api('issues')->comments(), 'all', [$username, $repository, $issueNumber]);
  }

  /**
   * Updates an issue.
   *
   * @param string $username
   *   Repository owner.
   * @param string $repository
   *   Repository name.
   * @param int $issueNumber
   *   Repo-scoped issue number.
   * @param array $params
   *   Fields to update, e.g. ['state' => 'closed'] or ['state' => 'open'].
   *
   * @return array
   *   The updated issue payload returned by GitHub, so callers do not need a
   *   second request to observe the new state.
   */
  public function updateIssue(string $username, string $repository, int $issueNumber, array $params): array {
    $this->init();
    return $this->client->issue()->update($username, $repository, $issueNumber, $params);
  }

  /**
   * Adds assignees to an issue.
   *
   * Additive: assignees already set on the issue are left untouched, so this
   * never silently drops someone else's assignment.
   *
   * @param string $username
   *   Repository owner.
   * @param string $repository
   *   Repository name.
   * @param int $issueNumber
   *   Repo-scoped issue number.
   * @param array $assignees
   *   Usernames to add.
   *
   * @return array
   *   The updated issue payload returned by GitHub.
   */
  public function addAssignees(string $username, string $repository, int $issueNumber, array $assignees): array {
    $this->init();
    return $this->client->issue()->assignees()->add($username, $repository, $issueNumber, ['assignees' => $assignees]);
  }

  /**
   * Removes assignees from an issue.
   *
   * Subtractive counterpart of ::addAssignees(): the assignees that are not
   * listed stay in place.
   *
   * @param string $username
   *   Repository owner.
   * @param string $repository
   *   Repository name.
   * @param int $issueNumber
   *   Repo-scoped issue number.
   * @param array $assignees
   *   Usernames to remove.
   *
   * @return array
   *   The updated issue payload returned by GitHub.
   */
  public function removeAssignees(string $username, string $repository, int $issueNumber, array $assignees): array {
    $this->init();
    return $this->client->issue()->assignees()->remove($username, $repository, $issueNumber, ['assignees' => $assignees]);
  }

  /**
   * Lists the timeline events of an issue.
   *
   * Includes the `referenced` and `cross-referenced` events that record which
   * commits mention the issue.
   *
   * ::configure() is called explicitly: Issue::timeline() returns a bare
   * Timeline instance, and without it the mockingbird preview Accept header
   * this endpoint expects is never sent.
   *
   * @param string $username
   *   Repository owner.
   * @param string $repository
   *   Repository name.
   * @param int $issueNumber
   *   Repo-scoped issue number.
   *
   * @return array
   *   Raw timeline event payloads.
   */
  public function listIssueTimeline(string $username, string $repository, int $issueNumber): array {
    $this->init();
    return $this->client->issue()->timeline()->configure()->all($username, $repository, $issueNumber);
  }

  /**
   * Creates a comment on an issue.
   *
   * ⚠️ NOT idempotent: calling this twice publishes two comments, visible to
   * everyone on the repository and with no way to undo them from here. Callers
   * must never retry it automatically.
   *
   * @param string $username
   *   Repository owner.
   * @param string $repository
   *   Repository name.
   * @param int $issueNumber
   *   Repo-scoped issue number.
   * @param string $body
   *   Comment body, in GitHub-flavoured markdown.
   *
   * @return array
   *   The created comment payload (id, user, body, created_at, html_url…),
   *   so the caller can store it without a second request.
   */
  public function createIssueComment(string $username, string $repository, int $issueNumber, string $body): array {
    $this->init();
    return $this->client->issue()->comments()->create($username, $repository, $issueNumber, ['body' => $body]);
  }

  /**
   * Shows a single commit.
   *
   * @param string $username
   *   Repository owner.
   * @param string $repository
   *   Repository name.
   * @param string $sha
   *   Full or abbreviated commit SHA.
   *
   * @return array
   *   The commit payload (sha, commit.message, commit.author, html_url…).
   */
  public function showCommit(string $username, string $repository, string $sha): array {
    $this->init();
    return $this->client->repository()->commits()->show($username, $repository, $sha);
  }

  /**
   * Lists the milestones of a repository.
   *
   * Pass ['state' => 'all'] to get closed milestones too; the GitHub default
   * is 'open' only.
   *
   * WARNING — do NOT try to order these by update date. This endpoint only
   * accepts `sort=due_date|completeness`, and BOTH layers below discard
   * anything else without a word:
   *   - Github\Api\Issue\Milestones::all() silently rewrites an unknown
   *     `sort` to 'due_date' before the request is even sent;
   *   - GitHub itself ignores an unknown `sort` and falls back to an
   *     undocumented order.
   * Measured on 2026-08-14 against agencekali/clearblue_v4 (24 milestones):
   * `sort=updated` and `sort=zzz_inexistant` return a byte-identical list —
   * and that list happens to be exactly updated_at DESC, which is precisely
   * what makes the trap so convincing. A cursor built on it would silently
   * skip milestones, the way it did on GitLab (uzinasit#468).
   * Read the whole list instead; repositories hold a handful of milestones.
   *
   * @param string $username
   *   Repository owner.
   * @param string $repository
   *   Repository name.
   * @param array $additionalParams
   *   Extra query parameters, e.g. ['state' => 'all'].
   *
   * @return array
   *   The milestones (number, title, description, state, due_on, updated_at…).
   */
  public function listMilestones(string $username, string $repository, $additionalParams = []): array {
    $this->init();
    $paginator = new ResultPager($this->client);
    $parameters = [$username, $repository, $additionalParams];
    return $paginator->fetchAll($this->client->api('issues')->milestones(), 'all', $parameters);
  }

  /**
   * Lists the pull requests of a repository.
   *
   * Unlike listMilestones(), this endpoint DOES honour
   * ['sort' => 'updated', 'direction' => 'desc'] — verified on 2026-08-14 with
   * the same control (an invalid `sort` value falls back to the default order,
   * while `sort=updated` genuinely reorders). It has no `since` parameter
   * though, unlike the issues endpoint.
   *
   * @param string $username
   *   Repository owner.
   * @param string $repository
   *   Repository name.
   * @param array $additionalParams
   *   Extra query parameters, e.g. ['state' => 'all'].
   *
   * @return array
   *   The pull requests (number, title, state, head.ref, base.ref…).
   */
  public function listPullRequests(string $username, string $repository, $additionalParams = []): array {
    $this->init();
    $paginator = new ResultPager($this->client);
    $parameters = [$username, $repository, $additionalParams];
    return $paginator->fetchAll($this->client->api('pull_request'), 'all', $parameters);
  }

  /**
   * Lists the releases of a repository.
   *
   * Returned by created_at descending. Note that editing a release does NOT
   * change its created_at, so an old release whose notes are fixed will never
   * move back to the top: this endpoint cannot support an incremental cursor
   * at all, only a full read.
   *
   * @param string $username
   *   Repository owner.
   * @param string $repository
   *   Repository name.
   * @param array $additionalParams
   *   Extra query parameters.
   *
   * @return array
   *   The releases (tag_name, name, body, draft, prerelease, published_at…).
   */
  public function listReleases(string $username, string $repository, $additionalParams = []): array {
    $this->init();
    $paginator = new ResultPager($this->client);
    $parameters = [$username, $repository, $additionalParams];
    return $paginator->fetchAll($this->client->api('repo')->releases(), 'all', $parameters);
  }

}
