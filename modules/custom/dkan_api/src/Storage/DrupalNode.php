<?php

namespace Drupal\dkan_api\Storage;

use Drupal\node\Entity\Node;

abstract class DrupalNode implements \Sae\Contracts\Storage
{
  abstract protected function getType();

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

  public function store($data, $id = Null) {
    $node = Node::create(['type' => $this->getType(), 'field_json_metadata' => $data]);
    $node->save();
    $json = $node->field_json_metadata->value;
    $decode = json_decode($json);
    return $decode->identifier;
  }

  public function remove($id) {
    // TODO: Implement remove() method.
  }
}