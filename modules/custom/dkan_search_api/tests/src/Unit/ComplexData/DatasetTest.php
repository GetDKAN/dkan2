<?php

use PHPUnit\Framework\TestCase;
use MockChain\Options;
use Drupal\Core\DependencyInjection\Container;

class DatasetTest extends TestCase
{
  public function test() {
    $schema = '
    {
      "$id": "https://example.com/person.schema.json",
      "$schema": "http://json-schema.org/draft-07/schema#",
      "title": "Person",
      "type": "object",
      "properties": {
        "firstName": {
          "type": "string",
          "description": "The person\'s first name."
        },
        "lastName": {
          "type": "string",
          "description": "The person\'s last name."
        },
        "age": {
          "description": "Age in years which must be equal to or greater than zero.",
          "type": "integer",
          "minimum": 0
        }
      }
    }
    ';

    $options = (new Options())
      ->add('dkan_schema.schema_retriever', \Drupal\dkan_schema\SchemaRetriever::class);


    $container = (new MockChain\Chain($this))
      ->add(Container::class, "get", $options)
      ->add(\Drupal\dkan_schema\SchemaRetriever::class, 'retrieve', $schema)
      ->getMock();

    \Drupal::setContainer($container);

    $thing = (object) ['firstName' => 'hello', 'lastName' => 'goodbye', 'age' => 5000];
    $json = json_encode($thing);
    $dataset = new \Drupal\dkan_search_api\ComplexData\Dataset($json);
    $this->assertEquals($json, json_encode($dataset->getValue()));

    $properties = $dataset->getProperties();
    $this->assertEquals(3, count($properties));
  }
}
