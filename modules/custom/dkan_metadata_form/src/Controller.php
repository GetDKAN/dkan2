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
    return [
      '#markup' => '<div id="app"></div>',
      '#attached' => [
        'library' => [
          'dkan_metadata_form/dkan_metadata_form',
        ],
      ],
    ];
  }

}