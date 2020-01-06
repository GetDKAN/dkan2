<?php

namespace Drupal\Tests\dkan_metastore\Unit;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\dkan_data\Plugin\DataProtectorManager;
use Drupal\dkan_data\Plugin\DataProtectorBase;
use PHPUnit\Framework\TestCase;
use Drupal\Core\Serialization\Yaml;
use Drupal\dkan_api\Controller\Docs;
use MockChain\Chain;
use MockChain\Options;
use Drupal\dkan_metastore\Service;
use Drupal\dkan_metastore\WebServiceApiDocs;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class.
 */
class WebServiceApiDocsTest extends TestCase {

  /**
   * Tests dataset-specific docs without data protector plugin.
   */
  public function testDatasetSpecificDocsWithoutSqlProtector() {
    $dataset = json_encode(['distribution' => [
      [
        'identifier' => 'dist-1234',
        'data' => [
          'title' => 'Title',
          'description' => 'Description',
        ],
      ],
    ]]);

    $mockChain = $this->getCommonMockChain()
      ->add(Service::class, "get", $dataset)
      ->add(DataProtectorManager::class, 'getDefinitions', [])
      ->add(SelectInterface::class, 'fetchCol', []);

    $controller = WebServiceApiDocs::create($mockChain->getMock());
    $response = $controller->getDatasetSpecific(1);

    $spec = '{"openapi":"3.0.1","info":{"title":"API Documentation","version":"Alpha"},"tags":[{"name":"Dataset"},{"name":"SQL Query"}],"paths":{"\/api\/1\/metastore\/schemas\/dataset\/items\/1":{"get":{"summary":"Get this dataset","tags":["Dataset"],"responses":{"200":{"description":"Ok"}}}},"\/api\/1\/datastore\/sql?query=[SELECT * FROM dist-1234];":{"get":{"summary":"Title","tags":["SQL Query"],"responses":{"200":{"description":"Ok"}},"description":"Description"}}}}';

    $this->assertEquals($spec, $response->getContent());
  }

  /**
   * Tests dataset-specific docs when SQL endpoint is protected.
   */
  public function testDatasetSpecificDocsWithSqlProtector() {
    $mockChain = $this->getCommonMockChain()
      ->add(Service::class, "get", "{}")
      ->add(DataProtectorManager::class, 'getDefinitions', [['id' => 'foobar']])
      ->add(DataProtectorManager::class, 'createInstance', DataProtectorBase::class)
      ->add(DataProtectorBase::class, 'requiresProtection', TRUE)
    ;

    $controller = WebServiceApiDocs::create($mockChain->getMock());
    $response = $controller->getDatasetSpecific(1);

    $spec = '{"openapi":"3.0.1","info":{"title":"API Documentation","version":"Alpha"},"tags":[{"name":"Dataset"}],"paths":{"\/api\/1\/metastore\/schemas\/dataset\/items\/1":{"get":{"summary":"Get this dataset","tags":["Dataset"],"responses":{"200":{"description":"Ok"}}}}}}';

    $this->assertEquals($spec, $response->getContent());
  }

  /**
   *
   */
  private function getCommonMockChain() {
    $serializer = new Yaml();
    // Test against ./docs/dkan_api_openapi_spec.yml.
    $yamlSpec = file_get_contents(__DIR__ . "/docs/dkan_api_openapi_spec.yml");

    $mockChain = new Chain($this);
    $mockChain->add(ContainerInterface::class, 'get',
      (new Options)->add('dkan_api.docs', Docs::class)
        ->add('dkan_metastore.service', Service::class)
        ->add('plugin.manager.dkan_data.protector', DataProtectorManager::class)
    )
      ->add(Docs::class, "getJsonFromYmlFile", $serializer->decode($yamlSpec));

    return $mockChain;
  }

}
