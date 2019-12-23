<?php

namespace Drupal\dkan_data\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DkanSolrSettingsForm.
 *
 * @package Drupal\dkan_data\Form
 * @codeCoverageIgnore
 */
class DkanSolrSettingsForm extends ConfigFormBase {

  /**
   * Inherited.
   *
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dkan_data.settings',
    ];
  }

  /**
   * Inherited.
   *
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dkan_solr_settings_form';
  }

  /**
   * Inherited.
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dkan_data.settings');
    $form['solr'] = array(
      '#type' => 'checkbox',
      '#title' => $this
          ->t('Use Solr search'),
      '#description' => $this->t('Click here to enable solr indexing.'),
      '#default_value' => $config->get('solr'),
    );

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('dkan_data.settings')
      ->set('solr', $form_state->getValue('solr'))
      ->save();

    // Rebuild routes, without clearing all caches.
    \Drupal::service("router.builder")->rebuild();
  } 

}
