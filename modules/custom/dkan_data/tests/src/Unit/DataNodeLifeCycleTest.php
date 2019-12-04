<?php

namespace Drupal\Tests\dkan_data\Unit;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityInterface;
use Drupal\dkan_common\Tests\Mock\Chain;
use Drupal\dkan_common\Tests\Mock\Options;
use Drupal\dkan_common\UrlHostTokenResolver;
use Drupal\dkan_data\DataNodeLifeCycle;
use Drupal\node\Entity\Node;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DataNodeLifeCycleTest extends TestCase
{
  public function testNotNode() {
    $this->expectExceptionMessage("We only work with nodes.");

    $entity = (new Chain($this))
      ->add(EntityInterface::class, "blah", null)
      ->getMock();

    new DataNodeLifeCycle($entity);
  }

  public function testNonDataNode() {
    $this->expectExceptionMessage("We only work with data nodes.");

    $node = (new Chain($this))
      ->add(Node::class, "bundle", "blah")
      ->getMock();

    new DataNodeLifeCycle($node);
  }

  public function testPresaveDistribution() {
    $container = (new Chain($this))
      ->add(Container::class, "get", RequestStack::class)
      ->add(RequestStack::class, "getCurrentRequest", Request::class)
      ->add(Request::class, "getHost", "dkan")
      ->add(Request::class, "getSchemeAndHttpHost", "http://dkan")
      ->getMock();

    \Drupal::setContainer($container);

    $metadata = (object) [
      "data" => (object)[
        "downloadURL" => "http://dkan/some/path/blah"
      ]
    ];

    $options = (new Options())
      ->add('field_json_metadata', (object) ["value" => json_encode($metadata)])
      ->add('field_data_type', (object) ["value" => "distribution"]);

    $nodeChain = new Chain($this);
    $node = $nodeChain
      ->add(Node::class, "bundle", "data")
      ->add(Node::class, "get", $options)
      ->add(Node::class, "set", null, "metadata")
      ->getMock();

    $lifeCycle = new DataNodeLifeCycle($node);
    $lifeCycle->presave();

    $metadata = $nodeChain->getStoredInput("metadata");

    $this->assertTrue((substr_count($metadata[1], UrlHostTokenResolver::TOKEN) > 0));
  }
}
