<?php

namespace Drupal\github_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Github API settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'github_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['github_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $url = 'https://github.com/settings/tokens/new?scopes=repo&description=Drupal%20github%20api%20' . date('Y-m-d');
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token'),
      '#required' => TRUE,
      '#default_value' => $this->config('github_api.settings')->get('token'),
      '#description' => $this->t('<a href="@url" target="_blank">Generate a token</a>', ['@url' => $url]),
    ];

    $form['my_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('My GitHub username'),
      '#default_value' => $this->config('github_api.settings')->get('my_username'),
      '#description' => $this->t('Your GitHub login. Used to auto-assign TMA tickets.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('github_api.settings')
      ->set('token', $form_state->getValue('token'))
      ->set('my_username', $form_state->getValue('my_username'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
