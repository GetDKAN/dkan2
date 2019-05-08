<?php

namespace Drupal\interra_frontend;

class InterraPage {

  public function build() {
    $drupalRoot = \Drupal::service('app.root');
    return file_get_contents($drupalRoot . "/data-catalog-frontend/build/index.html");
  }

}
