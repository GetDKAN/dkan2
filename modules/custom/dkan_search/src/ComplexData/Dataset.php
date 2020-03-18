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
      $type = $object->properties->{$property}->type;
      if ($type == "array"  && isset($object->properties->{$property}->items)) {
        $child_properties = array_keys((array) $object->properties->{$property}->items->properties);
        foreach ($child_properties as $child) {
          $definitions[$property . "__item__" . $child] = self::getDefinition($type);
        }
      }
      if ($type == "object" && isset($object->properties->{$property}->properties)) {
        $child_properties = array_keys((array) $object->properties->{$property}->properties);
        foreach($child_properties as $child) {
          $child_type = $object->properties->{$property}->properties->{$child}->type;
          if ($child_type == 'string') {
            $definitions[$property . "__" . $child] = self::getDefinition($child_type);
          }
        }
      }
      else {
        $definitions[$property] = self::getDefinition($type);
      }
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
