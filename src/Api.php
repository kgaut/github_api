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

}
