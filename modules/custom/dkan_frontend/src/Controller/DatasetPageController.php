<?php

namespace Drupal\dkan_frontend\Controller;

use Symfony\Component\HttpFoundation\Request;

class DatasetPageController {
  public function content($identifier, Request $request) {
    // This is a hardcoded node ID, you'll probably want to load 
    // this from config, or something.
    $nid = 303;
    $entity_type = 'node';
    $view_mode = 'default';

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $node = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['uuid' => $identifier]);
    
    // $build = $view_builder->view($node, $view_mode);
    return $node;
  }
}