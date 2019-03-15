<?php

namespace Drupal\dkan_datastore\Controller;

use Drupal\dkan_api\Controller\Api;
//use Drupal\dkan_api\Storage\DrupalNodeDataset;
//use Drupal\dkan_schema\SchemaRetriever;
//use JsonSchemaProvider\Provider;

class Datastore extends Api {

  private $connection;

  public function __construct() {
    //$this->nodeDataset = new DrupalNodeDataset();
  }

  protected function getStorage() {
    //return $this->nodeDataset;
  }

  public function runQuery($queryStr) {
  $provider = new Provider(new SchemaRetriever());
  return $provider->retrieve('dataset');
}

  protected function getJsonSchema() {
    parent::getJsonSchema();
  }

  public function get($uuid) {
    parent::get($uuid);
  }

  public function postAndGetAll() {
    parent::postAndGetAll();
  }

  public function getEngine() {
    parent::getEngine();
  }

}
