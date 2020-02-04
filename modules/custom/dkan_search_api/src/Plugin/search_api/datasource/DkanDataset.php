<?php

namespace Drupal\dkan_search_api\Plugin\search_api\datasource;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\dkan_search_api\ComplexData\Dataset;
use Drupal\node\Entity\Node;
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
    $pageSize = 250;
    $ids = [];
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'data')
      ->condition('field_data_type', 'dataset');

    $total = $query->count()->execute();
    $pages = floor($total / $pageSize);

    if ($page <= $pages) {

      $query = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'data')
        ->condition('field_data_type', 'dataset')
        ->range($page * $pageSize, $pageSize);
      $nids = $query->execute();

      foreach ($nids as $id) {
        $node = Node::load($id);
        $ids[] = $node->uuid();
      }

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
