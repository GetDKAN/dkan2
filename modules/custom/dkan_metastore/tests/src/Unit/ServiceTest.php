<?php

namespace Drupal\Tests\dkan_metastore\Unit;

use Drupal\dkan_data\Plugin\DataProtectorBase;
use Drupal\dkan_data\Plugin\DataProtectorManager;
use PHPUnit\Framework\TestCase;
use Sae\Sae as Engine;
use Drupal\Core\DependencyInjection\Container;
use MockChain\Chain;
use MockChain\Options;
use Drupal\dkan_metastore\Factory\Sae;
use Drupal\dkan_metastore\Service;
use Drupal\dkan_schema\SchemaRetriever;

/**
 *
 */
class ServiceTest extends TestCase {

  /**
   *
   */
  public function testGetSchemas() {
    $container = $this->getCommonMockChain()
      ->add(SchemaRetriever::class, "getAllIds", ["1"]);

    $service = Service::create($container->getMock());
    $this->assertEquals(json_encode(["1" => "blah"]), json_encode($service->getSchemas()));
  }

  /**
   *
   */
  public function testGetSchema() {
    $container = $this->getCommonMockChain();

    $service = Service::create($container->getMock());
    $this->assertEquals(json_encode("blah"), json_encode($service->getSchema("1")));
  }

  /**
   *
   */
  public function testGetAll() {
    $container = $this->getCommonMockChain()
      ->add(Sae::class, "getInstance", Engine::class)
      ->add(Engine::class, "get", [json_encode("blah")]);

    $service = Service::create($container->getMock());

    $this->assertEquals(json_encode(["blah"]), json_encode($service->getAll("dataset")));
  }

  /**
   *
   */
  public function testGet() {
    $json = json_encode(['foo' => 'bar']);
    $protected = (object) ['foo' => 'bar', 'protected' => 'processed'];

    $container = $this->getCommonMockChain()
      ->add(Sae::class, "getInstance", Engine::class)
      ->add(Engine::class, "get", $json)
      ->add(DataProtectorManager::class, 'getDefinitions', [['id' => 'foobar']])
      ->add(DataProtectorManager::class, 'createInstance', DataProtectorBase::class)
      ->add(DataProtectorBase::class, 'requiresProtection', TRUE)
      ->add(DataProtectorBase::class, 'protect', $protected);

    $service = Service::create($container->getMock());

    $this->assertEquals(json_encode(['foo' => 'bar', 'protected' => 'processed']), $service->get("dataset", "1"));
  }

  /**
   *
   */
  public function testGetResources() {
    $dataset = (object) [
      "identifier" => "1",
      "distribution" => [
        (object) ["title" => "hello"],
      ],
    ];

    $container = $this->getCommonMockChain()
      ->add(Sae::class, "getInstance", Engine::class)
      ->add(Engine::class, "get", json_encode($dataset));

    $service = Service::create($container->getMock());

    $this->assertEquals(json_encode([(object) ["title" => "hello"]]),
      json_encode($service->getResources("dataset", "1")));
  }

  /**
   *
   */
  public function testPost() {
    $container = $this->getCommonMockChain()
      ->add(Sae::class, "getInstance", Engine::class)
      ->add(Engine::class, "post", "1");

    $service = Service::create($container->getMock());

    $this->assertEquals("1", $service->post("dataset", json_encode("blah")));
  }

  /**
   *
   */
  public function testPut() {
    $container = $this->getCommonMockChain()
      ->add(Sae::class, "getInstance", Engine::class)
      ->add(Engine::class, "put", "1")
      ->add(Engine::class, "get", "1");

    $service = Service::create($container->getMock());

    $info = $service->put("dataset", "1", json_encode("blah"));
    $this->assertEquals("1", $info['identifier']);
  }

  /**
   *
   */
  public function testPatch() {
    $container = $this->getCommonMockChain()
      ->add(Sae::class, "getInstance", Engine::class)
      ->add(Engine::class, "patch", "1")
      ->add(Engine::class, "get", "1");

    $service = Service::create($container->getMock());

    $this->assertEquals("1", $service->patch("dataset", "1", json_encode("blah")));
  }

  /**
   *
   */
  public function testDelete() {
    $container = $this->getCommonMockChain()
      ->add(Sae::class, "getInstance", Engine::class)
      ->add(Engine::class, "delete", "1")
      ->add(Engine::class, "get", "1");

    $service = Service::create($container->getMock());

    $this->assertEquals("1", $service->delete("dataset", "1"));
  }

  /**
   * @return \Drupal\dkan_common\Tests\Mock\Chain
   */
  public function getCommonMockChain() {
    $options = (new Options())
      ->add('dkan_schema.schema_retriever', SchemaRetriever::class)
      ->add('dkan_metastore.sae_factory', Sae::class)
      ->add('plugin.manager.dkan_data.protector', DataProtectorManager::class);

    return (new Chain($this))
      ->add(Container::class, "get", $options)
      ->add(SchemaRetriever::class, "retrieve", json_encode("blah"))
      ->add(DataProtectorManager::class, 'getDefinitions', []);
  }

}
