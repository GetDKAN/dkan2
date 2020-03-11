<?php

namespace Drupal\dkan_search\ComplexData;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\ItemList;
use Drupal\Core\TypedData\TypedData;
use Drupal\dkan_search\Facade\ComplexDataFacade;

/**
 * Dataset.
 */
class Dataset extends ComplexDataFacade {
  private $data;

  /**
   * Definition.
   */
  public static function definition() {
    $definitions = [];

    /* @var  $schemaRetriever  SchemaRetriever */
    $schemaRetriever = \Drupal::service("dkan_schema.schema_retriever");
    $json = $schemaRetriever->retrieve("dataset");
    $object = json_decode($json);
    $properties = array_keys((array) $object->properties);

    foreach ($properties as $property) {
      $defs = [];
      $type = $object->properties->{$property}->type;
      if (($type == "array" && isset($object->properties->{$property}->items->properties))
        || $type == "object") {
        $defs = self::definitionHelper($object->properties->{$property}, $type, $property);
      }
      else {
        $defs[$property] = self::getDefinition($type);
      }
      $definitions = array_merge($definitions, $defs);
    }

    return $definitions;
  }

  /**
   * Private.
   */
  private static function definitionHelper($property_items, $type, $property_name) {
    $prefix = '';
    $definitions = [];
    $child_properties = [];
    if ($type == "array" && isset($property_items->items->properties)) {
      $prefix = $property_name . '__item__';
      $props = $property_items->items->properties;
      $child_properties = array_keys((array) $props);
    }
    elseif ($type == "object" && isset($property_items->properties)) {
      $prefix = $property_name . '__';
      $props = $property_items->properties;
      $child_properties = array_keys((array) $props);
    }
    else {
      $defs[$property_name] = self::getDefinition($type);
    }

    foreach ($child_properties as $child) {
      $definitions[$prefix . $child] = self::getDefinition($type);
    }
    return $definitions;
  }

  /**
   * Private.
   */
  private static function getDefinition($type) {
    if ($type == "object" || $type == "any") {
      $type = "string";
    }

    if ($type == "array") {
      return ListDataDefinition::create("string");
    }
    return DataDefinition::createFromDataType($type);
  }

  /**
   * Constructor.
   */
  public function __construct(string $json) {
    $this->data = json_decode($json);
  }

  /**
   * Inherited.
   *
   * @inheritDoc
   */
  public function get($property_name) {
    $definitions = self::definition();

    if (!isset($definitions[$property_name])) {
      return NULL;
    }

    $definition = $definitions[$property_name];

    if ($definition instanceof ListDataDefinition) {
      $property = new ItemList($definition, $property_name);
      $values = [];
      if (preg_match('/(.*)__item__(.*)/', $property_name, $matches)) {
        foreach ($this->data->{$matches[1]} as $dist) {
          $values[] = $dist->{$matches[2]};
        }
      }
      else {
        $values = $this->data->{$property_name};
        if (is_string($values)) {
          $values = json_decode($values);
        }
      }
      $property->setValue($values);
    }
    else {
      $matches = [];
      // Check if property corresponds to an object.
      if (preg_match('/(.*)__(.*)/', $property_name, $matches)) {
        if (isset($matches[1]) && isset($matches[2])) {
          $value = $this->data->{$matches[1]}->{$matches[2]};
        }
      }
      else {
        $value = $this->data->{$property_name};
      }
      $property = new class ($definition, $property_name) extends TypedData{};
      $property->setValue($value);
    }

    return $property;
  }

  /**
   * Inherited.
   *
   * @inheritDoc
   */
  public function getProperties($include_computed = FALSE) {
    $definitions = self::definition();
    $properties = [];
    foreach (array_keys($definitions) as $propertyName) {
      $properties[$propertyName] = $this->get($propertyName);
    }
    return $properties;
  }

  /**
   * Inherited.
   *
   * @inheritDoc
   */
  public function getValue() {
    return $this->data;
  }

}
