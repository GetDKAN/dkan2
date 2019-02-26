<?php

namespace Drupal\dkan_harvest\Extract;

class DataJson extends Extract {

  public function run() {
    $items = [];
    $this->log('DEBUG', 'extract', 'Running DataJson extraction.');
    $harvestFolder = $this->folder . '/' . $this->sourceId;

    $files_pattern = "$harvestFolder/*.json";

    if (count(glob($files_pattern)) == 0) {
      $this->cache();
    }

    foreach(glob($files_pattern) as $file) {
      $items[] = json_decode(file_get_contents($file));
    }
    return $items;
  }

  public function cache() {
    $this->log('DEBUG', 'extract', 'Caching DataJson files.');
		$data = $this->httpRequest($this->uri);
		$res = json_decode($data);
		if ($res->dataset) {
			foreach ($res->dataset as $dataset) {
        if (filter_var($dataset->identifier, FILTER_VALIDATE_URL)) {
          $i = explode("/", $dataset->identifier);
          $id = end($i);
        }
        else {
          $id = $dataset->identifier;
        }
        $this->writeToFile($id, json_encode($dataset));
			}
		}
  }

}
