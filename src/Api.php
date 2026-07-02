<?php

namespace Drupal\github_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Github\AuthMethod;
use Github\Client;
use Github\HttpClient\Message\ResponseMediator;
use Github\ResultPager;

/**
 * Provides a service wrapping the KnpLabs GitHub API client.
 */
class Api {

  /**
   * The module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * The authenticated GitHub API client.
   *
   * @var \Github\Client
   */
  protected Client $client;

  /**
   * Constructs an Api object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('github_api.settings');
  }

  /**
   * Lazily instantiates and authenticates the GitHub client.
   */
  protected function init() {
    if (!isset($this->client)) {
      $this->client = new Client();
      $this->client->authenticate($this->config->get('token'), NULL, AuthMethod::ACCESS_TOKEN);
    }
  }

  /**
   * Returns all repositories accessible to the authenticated user.
   *
   * @param bool $simple
   *   Whether to return a simplified representation.
   * @param bool $includeArchived
   *   Whether to include archived repositories.
   * @param array $additionalParams
   *   Additional parameters passed to the GitHub API.
   *
   * @return array
   *   The list of repositories.
   *
   * @throws \Http\Client\Exception
   */
  public function listProjects(bool $simple = TRUE, $includeArchived = FALSE, $additionalParams = []): array {
    $this->init();
    $paginator = new ResultPager($this->client);
    $parameters = [];

    return $paginator->fetchAll($this->client->api('user'), 'myRepositories', $parameters);
  }

  /**
   * Returns information about a given repository.
   *
   * @param int|string $project_id
   *   The repository id.
   *
   * @return array
   *   The repository information.
   */
  public function showProject($project_id): array {
    $this->init();
    $project = $this->client->repository()->showById($project_id);
    return is_array($project) ? $project : [];
  }

  /**
   * Returns all issues of a repository.
   *
   * @param string $username
   *   The repository owner.
   * @param string $repository
   *   The repository name.
   * @param array $additionalParams
   *   Additional parameters passed to the GitHub API.
   *
   * @return array
   *   The list of issues.
   *
   * @throws \Http\Client\Exception
   */
  public function listIssues(string $username, string $repository, $additionalParams = []): array {
    $this->init();
    $paginator = new ResultPager($this->client);
    $parameters = [$username, $repository, $additionalParams];
    return $paginator->fetchAll($this->client->api('issues'), 'all', $parameters);
  }

  /**
   * Returns a single issue.
   *
   * @param string $username
   *   The repository owner.
   * @param string $repository
   *   The repository name.
   * @param int $issue_id
   *   The issue number.
   *
   * @return array
   *   The issue payload.
   */
  public function showIssue(string $username, string $repository, int $issue_id): array {
    $this->init();
    $issue = $this->client->issue()->show($username, $repository, $issue_id);
    return is_array($issue) ? $issue : [];
  }

  /**
   * Returns the GitHub username configured for this site.
   *
   * @return string
   *   The GitHub login used to auto-assign tickets, or an empty string.
   */
  public function getMyUsername(): string {
    return (string) ($this->config->get('my_username') ?? '');
  }

  /**
   * Creates an issue in a repository.
   *
   * @param string $username
   *   The repository owner.
   * @param string $repository
   *   The repository name.
   * @param string $title
   *   The issue title.
   * @param string $body
   *   The issue body.
   * @param array $assignees
   *   The GitHub logins to assign to the issue.
   *
   * @return array
   *   The created issue payload.
   */
  public function createIssue(string $username, string $repository, string $title, string $body = '', array $assignees = []): array {
    $this->init();
    $params = ['title' => $title, 'body' => $body];
    if ($assignees !== []) {
      $params['assignees'] = $assignees;
    }
    $issue = $this->client->issue()->create($username, $repository, $params);
    return is_array($issue) ? $issue : [];
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
      json_encode(['sub_issue_id' => $subIssueId], JSON_THROW_ON_ERROR)
    );
    $content = ResponseMediator::getContent($response);
    return is_array($content) ? $content : [];
  }

}
