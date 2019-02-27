<?php

namespace Drupal\dkan_harvest\Storage;


use Contracts\Storage;

class Source extends Cruder implements Storage {

  public function index() {
    $items = $this->pRead('harvest_source', array('source_id'));
    return $items;
  }

  private function create($sourceId, $config) {
    $result = $this->db->insert('harvest_source')
      ->fields([
        'source_id' => $sourceId,
        'config' => json_encode($config),
      ])
      ->execute();
    return $result;
  }

  private function update($sourceId, $config) {
    $result = $this->db->update('harvest_source')
      ->fields([
        'source_id' => $sourceId,
        'config' => $config,
      ])
      ->execute();
    return $result;
  }

  private function delete($sourceId) {
    $result = $this->pDelete('harvest_source', 'source_id', $sourceId);
    return $result;
  }

  public function retrieve($id) {
    $items = $this->pRead('harvest_source', array('source_id', 'config'), $id);

    if (isset($items['config'][0])) {
      return json_decode($items['config'][0]);
    }
    return NULL;
  }


  public function store($data, $id = NULL) {
    if (!$id) {
      throw new \Exception("id is required");
    }

    $config = $this->retrieve($id);
    if ($config) {
      $this->update($id, $data);
    }
    else {
      $this->create($id, $data);
    }
  }

  public function remove($id) {
    $this->delete($id);
  }


}