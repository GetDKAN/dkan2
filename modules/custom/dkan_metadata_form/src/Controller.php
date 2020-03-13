<?php

namespace Drupal\dkan_metadata_form;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller.
 */
class Controller extends ControllerBase {

  /**
   * Page.
   */
  public function page() {

    // Generate a uuid for new datasets, pass it to the form.
    $uuid_service = \Drupal::service('uuid');
    $uuid = $uuid_service->generate();

    return [
      '#markup' => '<div id="app"></div>',
      '#attached' => [
        'library' => [
          'dkan_metadata_form/dkan_metadata_form',
        ],
        'drupalSettings' => [
          'tempUUID' => $uuid
        ]
      ],
    ];
  }
}