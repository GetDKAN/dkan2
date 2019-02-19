<?php

namespace Drupal\dkan_api\Storage;

use Drupal\node\Entity\Node;
use Contracts\Storage;
use Contracts\BulkRetriever;

class DrupalNodeDataset implements Storage, BulkRetriever {
  protected function getType() {
    return 'dataset';
  }

  public function retrieve($id) {
    $connection = \Drupal::database();
    $sql = "SELECT nid FROM node WHERE uuid = :uuid AND type = :type";
    $query = $connection->query($sql, [':uuid' => $id, ':type' => $this->getType()]);
    $results = $query->fetchAll();

    foreach ($results as $result) {
      $node = Node::load($result->nid);
      return $node->field_json_metadata->value;
    }

    throw new \Exception("No data with the identifier {$id} was found.");
  }

  public function retrieveAll() {
    $connection = \Drupal::database();
    $sql = "SELECT nid FROM node WHERE type = :type";
    $query = $connection->query($sql, [':type' => $this->getType()]);
    $results = $query->fetchAll();

    $all = [];
    foreach ($results as $result) {
      $node = Node::load($result->nid);
      $all[] = $node->field_json_metadata->value;
    }
    return "[" . implode(",", $all) . "]";
  }

  public function remove($id) {
    // TODO: Implement remove() method.
  }

  public function store($data, $id = NULL) {

    $data = json_decode($data);

    if (!$id && isset($data->identifier)) {
        $id = $data->identifier;
    }

    if ($id) {
        $node = \Drupal::service('entity.repository')->loadEntityByUuid('node', $id);
    }

    if ($node) {    // update existing node
      $node->field_json_metadata = json_encode($data);
      $node->save();
    } else {    // create new node
      $title = isset($data->title) ? $data->title : $data->name;
      $nodeWrapper = NODE::create([
        'title' => $title,
        'type' => 'dataset',
        'uuid' => $id,
        'field_json_metadata' => json_encode($data)
      ]);
      $nodeWrapper->save();
      return $nodeWrapper->id();
    }
  }

}
