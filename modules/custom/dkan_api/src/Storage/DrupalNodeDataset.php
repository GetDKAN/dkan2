<?php

namespace Drupal\dkan_api\Storage;


class DrupalNodeDataset extends DrupalNode {

  protected function getType() {
    return 'dataset';
  }

}