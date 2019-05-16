<?php

namespace Drupal\dkan_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ApiSettingsForm.
 *
 * @package Drupal\dkan_api\Form
 * @codeCoverageIgnore
 */
class ApiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dkan_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dkan_api.settings');
    $form['property_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enable API endpoints for the following dataset properties'),
      '#description' => $this->t('Separate properties by a new line.'),
      '#default_value' => $config->get('property_list'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('dkan_api.settings')
      ->set('property_list', $form_state->getValue('property_list'))
      ->save();
  }

}
