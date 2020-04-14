<?php


namespace Drupal\dkan_search;


class Service {

  public function search() {
    $storage = \Drupal::service("entity_type.manager")->getStorage('search_api_index');

    /* @var \Drupal\search_api\IndexInterface $index */
    $index = $storage->load('dkan');

    if (!$index) {
      throw new \Exception("An index named [dkan] does not exist.");
    }

    $thing = (object) ['title' => 'hello', 'description' => 'goodbye', 'publisher__name' => 'Steve'];

    $expect = (object) ['type' => 'publisher__name', 'name' => 'Steve', 'total' => 1];

    $responseBody = (object) [
      'total' => 1,
      'results' => [$thing],
      'facets' => [$expect],
    ];

    return $responseBody;
  }

  /**
   * @param string $fieldName
   *   Name of index field being queried.
   * @param string $fieldValue
   *   Value being searched.
   *
   * @return array
   *   Array of node ids.
   */
  public function searchByIndexField(string $fieldName, string $fieldValue) {
    return [1, 2];
  }

}
