<?php

namespace Drupal\votacao\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class VotacaoSettingsForm extends ConfigFormBase {

  public function getFormId(): string {
    return 'votacao_settings_form';
  }

  protected function getEditableConfigNames(): array {
    return ['votacao.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('votacao.settings');

    $form['voting_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable voting (global)'),
      '#default_value' => (bool) ($config->get('voting_enabled') ?? TRUE),
      '#description' => $this->t('When disabled, voting is blocked in the CMS and the external API.'),
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => (string) ($config->get('api_key') ?? ''),
      '#description' => $this->t('Clients must send this in the X-API-Key header.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->configFactory->getEditable('votacao.settings')
      ->set('voting_enabled', (bool) $form_state->getValue('voting_enabled'))
      ->set('api_key', (string) $form_state->getValue('api_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
