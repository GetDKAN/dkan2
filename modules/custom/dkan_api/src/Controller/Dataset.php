<?php

namespace Drupal\dkan_api\Controller;

use Drupal\dkan_api\Storage\DrupalNodeDataset;
use Drupal\dkan_schema\SchemaRetriever;
use JsonSchemaProvider\Provider;

class Dataset extends Api {

  private $nodeDataset;

  public function __construct() {
    $this->nodeDataset = new DrupalNodeDataset();
  }

  public function getStorage() {
    return $this->nodeDataset;
  }

  public function storeDataset($data) {
    $nid = $this->nodeDataset->store($data);
    return $nid;
  }

  protected function getJsonSchema() {
    $provider = new Provider(new SchemaRetriever());
    return $provider->retrieve('dataset');
  }
}
