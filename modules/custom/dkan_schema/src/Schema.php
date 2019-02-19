<?php

namespace Drupal\dkan_schema;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Serialization\Json;

class Schema {

  public $config = FALSE;

  function __construct() {
    $this->config = $this->loadConfig();
  }

  public function getCurrentSchema() {
    return $this->schema;
  }

  private function dir() {
      $schema_retriever =  new SchemaRetriever();
      return $schema_retriever->getSchemaDirectory();
  }

  private function loadConfig() {
    $file =  $this->dir() . '/config.yml';
    return Yaml::decode(file_get_contents($file));
  }

  public function getActiveBundles() {
    $bundles = [];
    foreach ($this->config['collections'] as $collection) {
      $bundles[] = $this->config['collectionToBundleMap'][$collection];
    }
    return $bundles;
  }

  public function getActiveCollections() {
    return $this->config['collections'];
  }

  public function getRouteCollections() {
    return $this->config['routeCollections'];
  }

  public function loadSchema($collection) {
    return $this->loadSchemaFile($collection);
  }

  public function prepareForForm($collection) {
    $schema = json_decode(file_get_contents($this->dir() . '/collections/' . $collection . '.json'));
    $references = $this->config['references'];
    // Currently we want to use strings for references. This will get fixed
    // later in the form definition itself.
    foreach ($references[$collection] as $reference => $entity) {
      $schema->properties->{$reference}->type = 'string';
      unset($schema->properties->{$reference}->items);
      unset($schema->properties->{$reference}->properties);
    }
    return $schema;
  }

  private function loadSchemaFile($collection) {
    return Json::decode(file_get_contents($this->dir() . '/collections/' . $collection . '.json'));
  }

  /**
   * Loads fully dereferenced schema.
   *
   * @param string $collection
   *   Provides ONLY that collection if supplied. Otherwise returns all
   *   collections.
   *
   * @return array
   *   Fully derefrenced schema.
   */
  public function loadFullSchema($collection = NULL) {
    $collections = $collection ? [$collection] : $this->getActiveCollections();
    $references = $this->config['references'];
    $fullSchama = array();
    foreach ($collections as $coll) {
      $dereferencedSchema = $this->loadSchema($coll);
      $fullSchema[$coll] = $this->dereference($references, $coll, $dereferencedSchema);
    }
    return $collection ? $fullSchema[$collection] : $fullSchema;
  }

  /**
   * Provides a deferenced version of a schema. The $references are set in
   * config. A little faster than a recursive array but could switch to that.
   * This is also only single dimensional which is probs OK.
   *
   * @param $references array
   *   Array with first level keys that are collections.
   * @param $collection string
   *   Collection to dereference.
   * @param $schema object
   *   The schema to load references for.
   *
   * @return object
   *   Derefenced schema.
   */
  private function dereference($references, $collection, $schema) {
    // No references are defined, so the schema is good to go.
    if (!isset($references[$collection])) {
      return $schema;
    }
    foreach ($references[$collection] as $reference) {
      $schema['properties'][$reference] = $this->loadSchemaFile($reference);
    }
    return $schema;
  }

}
