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

}
