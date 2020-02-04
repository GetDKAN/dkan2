<?php

namespace Drupal\dkan_search_api;

use Drupal\dkan_common\JsonResponseTrait;
use Drupal\dkan_metastore\Service;
use Drupal\search_api\Query\QueryInterface;

/**
 * Controller.
 */
class Controller {
  use JsonResponseTrait;

  /**
   * Search.
   */
  public function search() {
    $storage = \Drupal::service("entity.manager")->getStorage('search_api_index');

    /* @var \Drupal\search_api\IndexInterface $index */
    $index = $storage->load('dkan');

    if (!$index) {
      return $this->getResponse((object) ['message' => "An index named [dkan] does not exist."], 500);
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

    $end = ($params['page'] * $params['page-size']);
    $start = $end - $params['page-size'];
    $query->range($start, $params['page-size']);

    /* @var  $result ResultSet*/
    $result = $query->execute();
    $count = $result->getResultCount();

    $data = [];

    /* @var  $metastore Service */
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

  /**
   * Private.
   */
  private function getParams() {
    $defaults = [
      "page-size" => 10,
      "page" => 1,
    ];

    /* @var $requestStack RequestStack */
    $requestStack = \Drupal::service('request_stack');
    $request = $requestStack->getCurrentRequest();
    $params = $request->query->all();

    foreach ($defaults as $param => $default) {
      $params[$param] = isset($params[$param]) ? $params[$param] : $default;
    }

    if ($params["page-size"] > 100) {
      $params["page-size"] = 100;
    }

    return $params;
  }

  /**
   * Private.
   */
  private function setFullText(QueryInterface $query, $params, $index) {
    if (!isset($params['fulltext'])) {
      return;
    }

    $fulltextFields = $index->getFulltextFields();
    if (empty($fulltextFields)) {
      return;
    }

    $values = [];
    foreach ($fulltextFields as $field) {
      $values[$field][] = $params['fulltext'];
    }

    $this->createConditionGroup($query, $values, 'OR');
  }

  /**
   * Private.
   */
  private function setFieldConditions(QueryInterface $query, $fields, $params) {
    foreach ($fields as $field) {
      if (isset($params[$field])) {
        $values = [];
        foreach (explode(",", $params[$field]) as $value) {
          $values[$field][] = trim($value);
        }
        $this->createConditionGroup($query, $values);
      }
    }
  }

  /**
   * Private.
   */
  private function getFacets(QueryInterface $query, $fields) {
    $facetsTypes = ['theme', 'keyword'];
    $facets = [];

    /* @var  $metastore Service */
    $metastore = \Drupal::service("dkan_metastore.service");

    foreach ($facetsTypes as $type) {
      $inArray = in_array($type, $fields);
      if ($inArray) {
        foreach ($metastore->getAll($type) as $thing) {
          $myquery = clone $query;
          $myquery->addCondition($type, $thing->data);
          $result = $myquery->execute();
          $facets[] = [
            "type" => $type,
            "name" => $thing->data,
            'total' => $result->getResultCount(),
          ];
        }
      }
    }

    return $facets;
  }

  /**
   * Private.
   */
  private function setSort(QueryInterface $query, $params, $fields) {
    if (isset($params['sort']) && in_array($params['sort'], $fields)) {
      if (isset($params['sort-order']) && ($params['sort-order'] == 'asc' || $params['sort-order'] == 'desc')) {
        $order = ($params['sort-order'] == 'asc') ? $query::SORT_ASC : $query::SORT_DESC;
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

  /**
   * Private.
   */
  private function createConditionGroup(QueryInterface $query, $array, $conjuction = 'AND') {
    $cg = $query->createConditionGroup($conjuction);
    foreach ($array as $field => $values) {
      foreach ($values as $value) {
        $cg->addCondition($field, $value);
      }
    }
    $query->addConditionGroup($cg);
  }

}
