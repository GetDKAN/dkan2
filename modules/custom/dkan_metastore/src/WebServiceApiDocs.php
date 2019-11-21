<?php

namespace Drupal\dkan_metastore;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\dkan_api\Controller\Docs;
use Drupal\dkan_data\ValueReferencer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dataset-specific OpenAPI documentation.
 */
class WebServiceApiDocs implements ContainerInjectionInterface {
  use JsonResponseTrait;

  private $specOperationsToRemove = [
    'post',
    'put',
    'patch',
    'delete',
  ];

  private $specPathsToRemove = [
    '/api/1/metastore/schemas/dataset',
    '/api/1/metastore/schemas/dataset/items',
    '/api/1/metastore/schemas/{schema_id}/items',
    '/api/1/metastore/schemas/{schema_id}/items/{identifier}',
    '/api/1/harvest/plans',
    '/api/1/harvest/plans/{plan_id}',
    '/api/1/harvest/runs',
    '/api/1/harvest/runs/{run_id}',
    '/api/1/datastore/imports',
    '/api/1/datastore/imports/{identifier}',
    '/api/1',
    '/api/1/metastore/schemas/dataset/items/{identifier}/docs',
  ];

  /**
   * OpenAPI spec for dataset-related endpoints.
   *
   * @var \Drupal\dkan_api\Controller\Docs
   */
  private $docsController;

  /**
   * Metastore service.
   *
   * @var \Drupal\dkan_metastore\Service
   */
  private $metastoreService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new WebServiceApiDocs(
      $container->get("dkan_api.docs"),
      $container->get("dkan_metastore.service")
    );
  }

  /**
   * Constructs a new WebServiceApiDocs.
   *
   * @param \Drupal\dkan_api\Controller\Docs $docsController
   *   Serves openapi spec for dataset-related endpoints.
   * @param \Drupal\dkan_metastore\Service $metastoreService
   *   Metastore service.
   */
  public function __construct(Docs $docsController, Service $metastoreService) {
    $this->docsController = $docsController;
    $this->metastoreService = $metastoreService;
  }

  /**
   * Returns only dataset-specific GET requests for the API spec.
   *
   * @param string $identifier
   *   Dataset uuid.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   OpenAPI spec response.
   */
  public function getDatasetSpecific(string $identifier) {
    $spec = $this->docsController->getJsonFromYmlFile();

    // Keep only the GET requests.
    $spec = $this->removeSpecOperations($spec);

    // Remove GET dataset collection endpoint as well as property-related ones.
    // @TODO: consider flipping the logic, keeping array of paths interested in.
    $spec = $this->removeSpecPaths($spec);

    // Remove the security schemes.
    unset($spec['components']['securitySchemes']);
    // Remove required parameters, since now part of path.
    unset($spec['paths']['/api/v1/sql/{query}']['get']['parameters']);
    unset($spec['paths']['/api/v1/dataset/{uuid}']['get']['parameters']);
    // Keep only the tags needed, so remove the properties tag.
    $spec['tags'] = [
      ["name" => "Dataset"],
      ["name" => "SQL Query"],
    ];
    // Replace the dataset uuid placeholder.
    if (isset($spec['paths']['/api/v1/dataset/{uuid}'])) {
      $spec['paths']['/api/v1/dataset/' . $identifier] = $spec['paths']['/api/v1/dataset/{uuid}'];
      unset($spec['paths']['/api/v1/dataset/{uuid}']);
    }

    // Replace the sql endpoint query placeholder.
    $spec = $this->replaceDistributions($spec, $identifier);

    return $this->getResponse($spec);
  }

  /**
   * Removes operations from the api spec's paths.
   *
   * @param array $spec
   *   The original spec array.
   *
   * @return array
   *   Modified spec.
   */
  private function removeSpecOperations(array $spec) {
    if (isset($spec['paths'])) {
      foreach ($spec['paths'] as $path => $operations) {
        foreach ($operations as $op => $details) {
          if (in_array($op, $this->specOperationsToRemove)) {
            unset($spec['paths'][$path][$op]);
          }
        }
        if (empty($spec['paths'][$path])) {
          unset($spec['paths'][$path]);
        }
      }
    }

    return $spec;
  }

  /**
   * Remove paths from the api spec.
   *
   * @param array $spec
   *   The original spec array.
   *
   * @return array
   *   Modified spec.
   */
  private function removeSpecPaths(array $spec) {
    if (!isset($spec['paths'])) {
      return $spec;
    }
    foreach ($spec['paths'] as $path => $ops) {
      if (in_array($path, $this->specPathsToRemove)) {
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
   * @param string $identifier
   *   The dataset uuid.
   *
   * @return array
   *   Modified spec.
   */
  private function replaceDistributions(array $spec, string $identifier) {
    // Load this dataset's metadata with both data and identifiers.
    if (function_exists('drupal_static')) {
      drupal_static('dkan_data_dereference_method', ValueReferencer::DEREFERENCE_OUTPUT_REFERENCE_IDS);
    }

    $data = json_decode($this->metastoreService->get("dataset", $identifier));

    // Create and customize a path for each dataset distribution/resource.
    if (isset($data->distribution)) {
      foreach ($data->distribution as $dist) {
        $path = "/api/v1/sql/[SELECT * FROM {$dist->identifier}];";

        $spec['paths'][$path] = $spec['paths']['/api/v1/sql/{query}'];
        $spec['paths'][$path]['get']['summary'] = $dist->data->title ?? "";
        $spec['paths'][$path]['get']['description'] = $dist->data->description ?? "";
      }
      unset($spec['paths']['/api/v1/sql/{query}']);
    }
    return $spec;
  }

}
