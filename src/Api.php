<?php

namespace Drupal\github_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\gitlab_api\Entity\GitlabServer;
use Github\Client;
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
      $result = $this->client->authenticate($this->config->get('token'), NULL, Client::AUTH_ACCESS_TOKEN);
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

}
