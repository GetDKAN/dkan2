<?php
namespace Drupal\dkan_search_api\ComplexData;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\ItemList;
use Drupal\Core\TypedData\TypedData;
use Drupal\dkan_search_api\Facade\ComplexDataFacade;
use Drupal\dkan_schema\SchemaRetriever;

class Dataset extends ComplexDataFacade
{
  private $data;

  public static function definition() {
    $definitions = [];

    /** @var  $schemaRetriever  SchemaRetriever */
    $schemaRetriever = \Drupal::service("dkan_schema.schema_retriever");
    $json = $schemaRetriever->retrieve("dataset");
    $object = json_decode($json);
    $properties = array_keys((array) $object->properties);

    foreach ($properties as $property) {
      $type = $object->properties->{$property}->type;

      if ($type == "object" || $type == "any") {
        $type = "string";
      }

      if ($type == "array") {
        $definitions[$property] = ListDataDefinition::create("string");
      }
      else {
        $definitions[$property] = DataDefinition::createFromDataType($type);
      }
    }

    return $definitions;
  }

  public function __construct(string $json)
  {
    $this->data = json_decode($json);
  }

  /**
   * @inheritDoc
   */
  public function get($property_name)
  {
    $definitions = self::definition();

    if (!isset($definitions[$property_name])) {
      return NULL;
    }

    $definition = $definitions[$property_name];

    if ($definition instanceof ListDataDefinition) {
      $property = new ItemList($definition, $property_name);
      $values = $this->data->{$property_name};
      if (is_string($values)) {
        $values = json_decode($values);
      }
      $property->setValue($values);
    }
    else {
      $property = new class($definition, $property_name) extends TypedData {};
      $property->setValue($this->data->{$property_name});
    }

    return $property;
  }

  /**
   * @inheritDoc
   */
  public function getProperties($include_computed = false)
  {
    $definitions = self::definition();
    $properties = [];
    foreach (array_keys($definitions) as $propertyName) {
      $properties[$propertyName] = $this->get($propertyName);
    }
    return $properties;
  }

  /**
   * @inheritDoc
   */
  public function getValue()
  {
    return $this->data;
  }

}
