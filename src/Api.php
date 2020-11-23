<?php

namespace Drupal\github_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\gitlab_api\Entity\GitlabServer;
use Github\Client;

class Api {

  protected ImmutableConfig $config;

  protected Client $client;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('github_api.settings');
  }

  protected function init() {
    if (!isset($this->client)) {
      $this->client->authenticate($this->config->get('token'), NULL, Client::AUTH_ACCESS_TOKEN);
    }
  }

}
