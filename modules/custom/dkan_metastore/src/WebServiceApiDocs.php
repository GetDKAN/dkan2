<?php

namespace Drupal\dkan_metastore;

use Drupal\dkan_common\DataModifierPluginTrait;
use Drupal\dkan_common\Plugin\DataModifierManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\dkan_common\JsonResponseTrait;
use Drupal\dkan_data\Reference\Dereferencer;
use Drupal\dkan_api\Controller\Docs;

/**
 * Provides dataset-specific OpenAPI documentation.
 */
class WebServiceApiDocs implements ContainerInjectionInterface {
  use JsonResponseTrait;
  use DataModifierPluginTrait;

  /**
   * List of endpoints to keep for dataset-specific docs.
   *
   * Any combination of a path and any of its operations not specifically listed
   * below will be discarded.
   *
   * @var array
   */
  private $endpointsToKeep = [
    'metastore/schemas/dataset/items/{identifier}' => ['get'],
    'datastore/sql' => ['get'],
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
      $container->get("dkan_metastore.service"),
      $container->get('plugin.manager.dkan_common.data_modifier')
    );
  }

  /**
   * Constructs a new WebServiceApiDocs.
   *
   * @param \Drupal\dkan_api\Controller\Docs $docsController
   *   Serves openapi spec for dataset-related endpoints.
   * @param \Drupal\dkan_metastore\Service $metastoreService
   *   Metastore service.
   * @param \Drupal\dkan_common\Plugin\DataModifierManager $pluginManager
   *   Metastore plugin manager.
   */
  public function __construct(Docs $docsController, Service $metastoreService, DataModifierManager $pluginManager) {
    $this->docsController = $docsController;
    $this->metastoreService = $metastoreService;
    $this->pluginManager = $pluginManager;

    $this->plugins = $this->discover();
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
    $fullSpec = $this->docsController->getJsonFromYmlFile();

    // Remove the security schemes.
    unset($fullSpec['components']['securitySchemes']);

    // Tags can be added later when needed, remove them for now.
    $fullSpec['tags'] = [];

    $pathsAndOperations = $fullSpec['paths'];
    $pathsAndOperations = $this->keepDatasetSpecificEndpoints($pathsAndOperations);
    $pathsAndOperations = $this->modifyDatasetEndpoints($pathsAndOperations, $identifier);
    $pathsAndOperations = $this->modifySqlEndpoints($pathsAndOperations, $identifier, $fullSpec['components']['parameters']);

    $fullSpec['paths'] = $pathsAndOperations;
    return $this->getResponse($fullSpec);
  }

  /**
   * Keep only paths and operations relevant for our dataset-specific docs.
   *
   * @param array $pathsAndOperations
   *   The paths defined in the original spec.
   *
   * @return array
   *   Modified paths and operations array.
   */
  private function keepDatasetSpecificEndpoints(array $pathsAndOperations) {
    $keepPaths = array_keys($this->endpointsToKeep);

    $paths = array_keys($pathsAndOperations);

    $pathsToKeepPaths = array_combine($paths, array_map(function ($path) use ($keepPaths) {
      foreach ($keepPaths as $keepPath) {
        if (substr_count($path, $keepPath) > 0) {
          return $keepPath;
        }
      }
      return NULL;
    }, $paths));

    foreach ($pathsAndOperations as $path => $operations) {
      if (is_null($pathsToKeepPaths[$path])) {
        unset($pathsAndOperations[$path]);
      }
      else {
        $pathsAndOperations[$path] = array_filter($operations, function ($operation) use ($path, $pathsToKeepPaths) {
          return in_array($operation, $this->endpointsToKeep[$pathsToKeepPaths[$path]]);
        }, ARRAY_FILTER_USE_KEY);
      }
    }

    return $pathsAndOperations;
  }

  /**
   * Modify the generic dataset endpoint to be specific to the current dataset.
   *
   * @param array $pathsAndOperations
   *   The paths defined in the original spec.
   * @param string $identifier
   *   Dataset uuid.
   *
   * @return array
   *   Spec with dataset-specific metastore get endpoint.
   */
  private function modifyDatasetEndpoints(array $pathsAndOperations, string $identifier) {

    foreach ($pathsAndOperations as $path => $operations) {
      foreach ($operations as $operation => $info) {
        foreach ($info['parameters'] as $key => $parameter) {
          $this->modifyDatasetEndpoint($pathsAndOperations, $path, $operation, $key, $parameter, $identifier);
        }
      }
    }

    return $pathsAndOperations;
  }

  /**
   * Private.
   */
  private function modifyDatasetEndpoint(&$pathsAndOperations, $path, $operation, $parameterKey, $parameter, $identifier) {
    if (isset($parameter['name']) && $parameter['name'] == "identifier" && isset($parameter['example'])) {
      $newPath = str_replace("{identifier}", $identifier, $path);
      $pathsAndOperations[$newPath] = $pathsAndOperations[$path];
      unset($pathsAndOperations[$path]);
      $pathsAndOperations[$newPath][$operation]['parameters'][$parameterKey]['example'] = $identifier;
    }
  }

  /**
   * Modify the generic sql endpoint to be specific to the current dataset.
   *
   * @param array $pathsAndOperations
   *   The paths defined in the original spec.
   * @param string $identifier
   *   Dataset uuid.
   * @param array $parameters
   *   Original spec parameters.
   *
   * @return array
   *   Spec with dataset-specific datastore sql endpoint.
   */
  private function modifySqlEndpoints(array $pathsAndOperations, string $identifier, array $parameters) {
    if ($this->modifyData($identifier)) {
      foreach ($pathsAndOperations as $path => $operations) {
        if (substr_count($path, 'sql') > 0) {
          unset($pathsAndOperations[$path]);
        }
      }
    }
    else {
      foreach ($pathsAndOperations as $path => $operations) {
        if (substr_count($path, 'sql') > 0) {
          foreach ($this->getDistributions($identifier) as $dist) {
            $newPath = "/api/1/datastore/sql?query=[SELECT * FROM {$dist->identifier}];";

            $pathsAndOperations[$newPath] = $pathsAndOperations[$path];

            if (isset($dist->data->title)) {
              $pathsAndOperations[$newPath]['get']['summary'] = $dist->data->title;
            }
            if (isset($dist->data->description)) {
              $pathsAndOperations[$newPath]['get']['description'] = $dist->data->description;
            }

            $query = $parameters['query'];
            $query['example'] = "[SELECT * FROM {$dist->identifier}];";
            $pathsAndOperations[$newPath]['get']['parameters'] = [$query];
          }

          unset($pathsAndOperations[$path]);
        }
      }
    }
    return $pathsAndOperations;
  }

  /**
   * Provides data modifiers plugins an opportunity to act.
   *
   * @param string $identifier
   *   The distribution's identifier.
   *
   * @return bool
   *   TRUE if sql endpoint docs needs to be protected, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function modifyData(string $identifier) {
    foreach ($this->plugins as $plugin) {
      if ($plugin->requiresModification('distribution', $identifier)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get a dataset's resources/distributions.
   *
   * @param string $identifier
   *   The dataset uuid.
   *
   * @return array
   *   Modified spec.
   */
  private function getDistributions(string $identifier) {
    // Load this dataset's metadata with both data and identifiers.
    if (function_exists('drupal_static')) {
      drupal_static('dkan_data_dereference_method', Dereferencer::DEREFERENCE_OUTPUT_REFERENCE_IDS);
    }

    $data = json_decode($this->metastoreService->get("dataset", $identifier));

    // Create and customize a path for each dataset distribution/resource.
    if (isset($data->distribution)) {
      return $data->distribution;
    }
    return [];
  }

}
