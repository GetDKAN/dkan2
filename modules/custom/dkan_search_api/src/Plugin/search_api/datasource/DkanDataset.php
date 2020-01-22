<?php

namespace Drupal\dkan_search_api\Plugin\search_api\datasource;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\dkan_search_api\ComplexData\Dataset;
use Drupal\search_api\Datasource\DatasourcePluginBase;

/**
 * Represents a datasource which exposes DKAN data.
 *
 * @SearchApiDatasource(
 *   id = "dkan_dataset",
 *   label = "DKAN Dataset",
 * )
 */
class DkanDataset extends DatasourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return Dataset::definition();
  }

  /**
   *
   */
  public function getItemIds($page = NULL) {
    global $firstTime;

    if (!isset($page) || $page == 0) {
      /* @var  $dataStorage  \Drupal\dkan_data\Storage\Data */
      $dataStorage = \Drupal::service("dkan_data.storage");
      $dataStorage->setSchema('dataset');
      $objects = $dataStorage->retrieveAll();
      $ids = array_map(function ($json) {
        $object = json_decode($json);
        return $object->identifier;
      }, $objects);
      $firstTime = FALSE;
      return $ids;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids) {
    /* @var  $dataStorage  \Drupal\dkan_data\Storage\Data */
    $dataStorage = \Drupal::service("dkan_data.storage");
    $dataStorage->setSchema('dataset');

    $items = [];
    foreach ($ids as $id) {
      $items[$id] = new Dataset($dataStorage->retrieve($id));
    }

    return $items;
  }

  /**
   * @inheritDoc
   */
  public function getItemId(ComplexDataInterface $item) {
    return $item->get('identifier');
  }

}
