<?php

namespace Drupal\dkan_search_api;

use Drupal\dkan_common\JsonResponseTrait;
use Drupal\dkan_metastore\Service;
use Drupal\search_api\Query\ResultSet;

class Controller
{
  use JsonResponseTrait;

  public function search() {
    $defaults = [
      "pageSize" => 10,
      "page" => 1
    ];

    $params = \Drupal::request()->query->all();

    foreach ($defaults as $param => $default) {
      $params[$param] = isset($params[$param]) ? $params[$param] : $default;
    }

    /* @var $qh \Drupal\search_api\Utility\QueryHelper */
    $qh = \Drupal::service("search_api.query_helper");

    $storage = \Drupal::service("entity.manager")->getStorage('search_api_index');

    /** @var  $metastore Service */
    $metastore = \Drupal::service("dkan_metastore.service");

    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $storage->load('index_1');

    $query = $qh->createQuery($index);

    if ($params['fulltext']) {
      $fulltextFields = $index->getFulltextFields();
      $cg = $query->createConditionGroup('OR');
      foreach ($fulltextFields as $field) {
        $cg->addCondition($field, $params['fulltext']);
      }
      $query->addConditionGroup($cg);
    }

    $query->sort('search_api_relevance', $query::SORT_DESC);

    $end = ($params['page'] * $params['pageSize']);
    $start = $end - $params['pageSize'];
    $query->range($start, $params['pageSize']);

    /** @var  $result ResultSet*/
    $result = $query->execute();
    $count = $result->getResultCount();

    $data = [];

    foreach ($result->getResultItems() as $item) {
      $id = $item->getId();
      $id = str_replace("dkan_dataset/", "", $id);
      $data[] = json_decode($metastore->get("dataset", $id));
    }

    $responseBody = (object) [
      'total' => $count,
      'results' => $data
    ];

    return $this->getResponse($responseBody);
  }
}
