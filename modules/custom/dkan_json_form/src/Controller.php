<?php
namespace Drupal\dkan_json_form;

use Drupal\Core\Controller\ControllerBase;

class Controller extends ControllerBase
{
  public function page() {
    return [
      '#markup' => '<div id="root"></div>',
      '#attached' => [
        'library' => [
          'dkan_json_form/dkan_json_form',
        ],
      ],
    ];
  }
}
