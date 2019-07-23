<?php
declare(strict_types=1);

namespace Drupal\dkan_api\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\dkan_data\ValueReferencer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Docs implements ContainerInjectionInterface {

  /**
   * The API array spec, to ease manipulation, before json encoding.
   *
   * @var array
   */
  protected $spec;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Factory to generate various dkan classes.
   *
   * @var \Drupal\dkan_common\Service\Factory
   */
  protected $dkanFactory;

  /**
   * Serializer to translate yaml to json.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $ymlSerializer;

  /**
   * Drupal node dataset storage.
   *
   * @var \Drupal\dkan_api\Storage\DrupalNodeDataset
   */
  protected $storage;

  /**
   * @{inheritdocs}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container) {
    return new static($container);
  }

  /**
   * Constructor.
   */
  public function __construct(ContainerInterface $container) {
    $this->moduleHandler = $container->get('module_handler');
    $this->dkanFactory = $container->get('dkan.factory');
    $this->ymlSerializer = $container->get('serialization.yaml');

    $this->storage = $container->get('dkan_api.storage.drupal_node_dataset');
    $this->storage->setSchema('dataset');

    $this->spec = $this->getJsonFromYmlFile();
  }

  /**
   * Load the yaml spec file and convert it to an array
   *
   * @return array
   */
  protected function getJsonFromYmlFile() {
    $modulePath = $this->moduleHandler->getModule('dkan_api')->getPath();
    $ymlSpecPath = $modulePath . '/docs/dkan_api_openapi_spec.yml';
    $ymlSpec = $this->fileGetContents($ymlSpecPath);

    return $this->ymlSerializer->decode($ymlSpec);
  }

  /**
   * Wrapper around file_get_contents to facilitate testing.
   *
   * @param string $path
   *
   * @return false|string
   *
   * @codeCoverageIgnore
   */
  protected function fileGetContents($path) {
    return file_get_contents($path);
  }

  /**
   * Returns the complete API spec.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getComplete() {
    $jsonSpec = json_encode($this->spec);

    return $this->sendResponse($jsonSpec);
  }

  /**
   * Returns only dataset-specific GET requests for the API spec.
   *
   * @param \Drupal\dkan_api\Controller\string $uuid
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getDatasetSpecific(string $uuid) {
    // Keep only the GET requests.
    $spec =  $this->removeSpecOperations($this->spec, [
      'post',
      'put',
      'patch',
      'delete',
    ]);
    // Remove GET dataset collection endpoint as well as property-related ones.
    $spec = $this->removeSpecPaths($spec, [
      '/api/v1/dataset',
      '/api/v1/{property}',
      '/api/v1/{property}/{uuid}',
    ]);
    // Remove the security schemes.
    unset($spec['components']);
    // Remove required parameters, since now part of path.
    unset($spec['paths']['/api/v1/sql/{query}']['get']['parameters']);
    unset($spec['paths']['/api/v1/dataset/{uuid}']['get']['parameters']);
    // Keep only the tags needed, so remove the properties tag.
    $spec['tags'] = [
      ["name" => "Dataset"],
      ["name" => "SQL Query"],
    ];
    // Replace the dataset uuid placeholder.
    $spec['paths']['/api/v1/dataset/' . $uuid] = $spec['paths']['/api/v1/dataset/{uuid}'];
    unset($spec['paths']['/api/v1/dataset/{uuid}']);
    // Replace the sql endpoint query placeholder.
    $spec = $this->replaceDistributions($spec, $uuid);

    $jsonSpec = json_encode($spec);
    return $this->sendResponse($jsonSpec);
  }

  /**
   * Helper function to set headers and send response.
   *
   * @param string $jsonSpecAnon
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function sendResponse(string $jsonSpecAnon) {
    $response = $this->dkanFactory
      ->newJsonResponse();
    $response->headers->set('Content-type', 'application/json');
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->setContent($jsonSpecAnon);

    return $response;
  }

  /**
   * Removes operations from the api spec's paths.
   *
   * @param array $spec
   *   The original spec array.
   * @param array $ops_to_remove
   *   Array of operations to be removed.
   *
   * @return array
   *   Modified spec.
   */
  protected function removeSpecOperations(array $spec, array $ops_to_remove) {
    foreach ($spec['paths'] as $path => $operations) {
      foreach ($operations as $op => $details) {
        if (in_array($op, $ops_to_remove)) {
          unset($spec['paths'][$path][$op]);
        }
      }
      if (empty($spec['paths'][$path])) {
        unset($spec['paths'][$path]);
      }
    }

    return $spec;
  }

  /**
   * Remove paths from the api spec.
   *
   * @param array $spec
   *   The original spec array.
   * @param array $paths_to_remove
   *   Array of paths to be removed.
   *
   * @return array
   *   Modified spec.
   */
  protected function removeSpecPaths(array $spec, array $paths_to_remove) {
    foreach ($spec['paths'] as $path => $ops) {
      if (in_array($path, $paths_to_remove)) {
        unset($spec['paths'][$path]);
      }
    }
    return $spec;
  }

  /**
   * Replace the sql {query} placeholder with dataset-specific distributions.
   *
   * @param array $spec
   *   The original spec array.
   * @param \Drupal\dkan_api\Controller\string $uuid
   *   The dataset uuid.
   *
   * @return array
   *   Modified spec.
   */
  protected function replaceDistributions(array $spec, string $uuid) {
    // Load this dataset's metadata with both data and identifiers.
    drupal_static('dkan_data_dereference_method', ValueReferencer::DEREFERENCE_OUTPUT_BOTH);
    $dataset = $this->storage->retrieve($uuid);
    $data = json_decode($dataset);

    // Create and customize a path for each dataset distribution/resource.
    foreach ($data->distribution as $dist) {
      $path = "/api/v1/sql/[SELECT * FROM {$dist->identifier}];";

      $spec['paths'][$path] = $spec['paths']['/api/v1/sql/{query}'];
      $spec['paths'][$path]['get']['summary'] = $dist->data->title ?? "";
      $spec['paths'][$path]['get']['description'] = $dist->data->description ?? "";
    }
    unset($spec['paths']['/api/v1/sql/{query}']);

    return $spec;
  }

}
