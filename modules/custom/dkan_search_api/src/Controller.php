<?php

namespace Drupal\dkan_search_api;

use Drupal\dkan_common\JsonResponseTrait;
use Drupal\dkan_metastore\Service;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSet;

class Controller
{
  use JsonResponseTrait;

  public function search() {
    $storage = \Drupal::service("entity.manager")->getStorage('search_api_index');
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $storage->load('dkan');

    if (!$index) {
      return $this->getResponse("No index name 'dkan' exists.", 500);
    }

    $fields = array_keys($index->getFields());

    $params = $this->getParams();

    /* @var $qh \Drupal\search_api\Utility\QueryHelper */
    $qh = \Drupal::service("search_api.query_helper");

    /* @var $query \Drupal\search_api\Query\QueryInterface */
    $query = $qh->createQuery($index);

    $this->setFullText($query, $params, $index);

    $this->setFieldConditions($query, $fields, $params);

    $facets = $this->getFacets($query, $fields);

    $this->setSort($query, $params, $fields);

    $end = ($params['page'] * $params['pageSize']);
    $start = $end - $params['pageSize'];
    $query->range($start, $params['pageSize']);

    /** @var  $result ResultSet*/
    $result = $query->execute();
    $count = $result->getResultCount();

    $data = [];

    /** @var  $metastore Service */
    $metastore = \Drupal::service("dkan_metastore.service");

    foreach ($result->getResultItems() as $item) {
      $id = $item->getId();
      $id = str_replace("dkan_dataset/", "", $id);
      $data[] = json_decode($metastore->get("dataset", $id));
    }

    $responseBody = (object) [
      'total' => $count,
      'results' => $data,
      'facets' => $facets,
    ];

    return $this->getResponse($responseBody);
  }

  private function getParams() {
    $defaults = [
      "pageSize" => 10,
      "page" => 1
    ];

    $params = \Drupal::request()->query->all();

    foreach ($defaults as $param => $default) {
      $params[$param] = isset($params[$param]) ? $params[$param] : $default;
    }

    if ($params["pageSize"] > 100) {
      $params["pageSize"] = 100;
    }

    return $params;
  }

  private function setFullText(QueryInterface $query, $params, $index) {
    if ($params['fulltext']) {
      $fulltextFields = $index->getFulltextFields();
      $cg = $query->createConditionGroup('OR');
      foreach ($fulltextFields as $field) {
        $cg->addCondition($field, $params['fulltext']);
      }
      $query->addConditionGroup($cg);
    }
  }

  private function setFieldConditions(QueryInterface $query, $fields, $params) {
    foreach ($fields as $field) {
      if (isset($params[$field])) {
        $cg = $query->createConditionGroup();
        foreach (explode(",", $params[$field]) as $value) {
          $cg->addCondition($field, trim($value));
        }
        $query->addConditionGroup($cg);
      }
    }
  }

  private function getFacets(QueryInterface $query, $fields) {
    $facetsTypes = ['theme', 'keyword'];
    $facets = [];

    /** @var  $metastore Service */
    $metastore = \Drupal::service("dkan_metastore.service");

    foreach($facetsTypes as $type) {
      if (in_array($type, $fields)) {
        foreach ($metastore->getAll($type) as $thing) {
          $myquery = clone $query;
          $myquery->addCondition($type, $thing->data);
          $result = $myquery->execute();
          $facets[] = [
            "type" => $type,
            "name" => $thing->data,
            'total' => $result->getResultCount()
          ];
        }
      }
    }

    return $facets;
  }

  private function setSort(QueryInterface $query, $params, $fields) {
    if (isset($params['sort']) && in_array($params['sort'], $fields)) {
      if(isset($params['sort_order']) && ($params['sort_order'] == 'asc' || $params['sort_order'] == 'desc')) {
        $order = ($params['sort_order'] == 'asc') ? $query::SORT_ASC : $query::SORT_DESC;
        $query->sort($params['sort'], $order);
      }
      else {
        $query->sort($params['sort'], $query::SORT_ASC);
      }
    }
    else {
      $query->sort('search_api_relevance', $query::SORT_DESC);
    }
  }
}
