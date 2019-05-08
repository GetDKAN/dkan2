<?php

namespace Drupal\interra_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\dkan_datastore\Util;
use Drupal\dkan_schema\SchemaRetriever;
use JsonSchemaProvider\Provider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\dkan_schema\Schema;
use Drupal\interra_api\Search;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * An ample controller.
 */
class ApiController extends ControllerBase {

  /**
   *
   */
  public function schemas(Request $request) {
    try {
      $schema = $this->fetchSchema('dataset');
    }
    catch (\Exception $e) {
      return $this->response($e->getMessage());
    }

    $data = ['dataset' => json_decode($schema)];
    return $this->jsonResponse($data);

  }

  /**
   *
   */
  public function schema($schema_name) {
    try {
      $schema = $this->fetchSchema($schema_name);
    }
    catch (\Exception $e) {
      return $this->response($e->getMessage());
    }

    return $this->jsonResponse(json_decode($schema));

  }

  /**
   *
   * @param string $schema_name
   * @return string Schema
   */
  protected function fetchSchema($schema_name) {
    $provider = $this->getSchemaProvider();
   return $provider->retrieve($schema_name);
  }


  public function search(Request $request)
  {
    /** @var Search $search */
    $search = \Drupal::service('interra_api.search');
    return $this->response($search->index());
  }

  /**
   *
   * @TODO very high CRAP score. consider refactoring. use routing to split to different method?
   * @param type $collection
   * @return type
   * @throws NotFoundHttpException
   */
  public function collection($collection)
  {
    $valid_collections = [
      'dataset',
      'organization',
      'theme',
    ];

    $collection = str_replace(".json", "", $collection);
    /** @var \Drupal\interra_api\Service\DatasetModifier $datasetModifier */
    $datasetModifier = \Drupal::service('interra_api.service.dataset_modifier');

    if (in_array($collection, $valid_collections)) {

      /** @var \Drupal\dkan_api\Storage\DrupalNodeDataset $storage */
      $storage = \Drupal::service('dkan_api.storage.drupal_node_dataset');
      $data = $storage->retrieveAll();

      if ($collection == "dataset") {
        $json = "[" . implode(",", $data) . "]";
        $decoded = json_decode($json);

        foreach ($decoded as $key => $dataset) {
          $decoded[$key] = $datasetModifier->modifyDataset($dataset);
        }

        return $this->response($decoded);
      }
      elseif ($collection == "theme") {
        $themes = [];
        foreach ($data as $dataset_json) {
          $dataset = json_decode($dataset_json);

          if ($dataset->theme && is_array($dataset->theme)) {
            $theme =  $datasetModifier->objectifyStringsArray($dataset->theme);
            $themes[$theme[0]->identifier] = $theme[0];
          }
        }

        ksort($themes);

        return $this->response(array_values($themes));
      }
      elseif ($collection == "organization") {
        $organizations = [];
        foreach ($data as $dataset_json) {
          $dataset = json_decode($dataset_json);

          if ($dataset->publisher) {
            $organizations[$dataset->publisher->name] = $dataset->publisher;
          }
        }

        ksort($organizations);

        return $this->response(array_values($organizations));
      }
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  /**
   *
   * @todo does not appear to be used in routes. Is this still needed?
   * @param type $collection
   * @param type $doc
   * @return type
   * @throws NotFoundHttpException
   */
  public function doc($collection, $doc)
  {
    $valid_collections = [
      'dataset',
    ];

    $uuid = str_replace(".json", "", $doc);

    if (in_array($collection, $valid_collections)) {

      if ($collection == "dataset") {

        /** @var \Drupal\dkan_api\Storage\DrupalNodeDataset $storage */
        $storage = \Drupal::service('dkan_api.storage.drupal_node_dataset');
        $data = $storage->retrieve($uuid);
        $dataset = json_decode($data);
        $dataset = $this->addDatastoreMetadata($dataset);
        return $this->response($datasetModifier->modifyDataset($dataset));
      }
      else {
        return $this->response([]);
      }
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  /**
   *
   * @TODO only used by `doc()` is this still needed?
   * @param type $dataset
   * @return type
   */
  /**
   *
   */
  private function addDatastoreMetadata($dataset) {
    $manager = $this->getDatastoreManager($dataset->identifier);

    if ($manager) {
      $headers = $manager->getTableHeaders();
      $dataset->columns = $headers;
      $dataset->datastore_statistics = [
        'rows' => $manager->numberOfRecordsImported(),
        'columns' => count($headers),
      ];
    }

    return $dataset;
  }

  /**
   *
   * @param mixed $resp
   * @return JsonResponse
   */
  protected function response($resp)
  {
    /** @var JsonResponse $response */
    $response = \Drupal::service('dkan.factory')
            ->newJsonResponse($resp);
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PATCH, DELETE');
    $response->headers->set('Access-Control-Allow-Headers', 'Authorization');
    return $response;
  }

  /**
   *
   * @param mixed $resp
   * @return JsonResponse
   */
  protected function jsonResponse($resp) {
    $response = $this->response($resp);
    // @todo is this necessary? it's already a JsonResponse object
    $response->headers->set("Content-Type", "application/schema+json");
    return $response;
  }

  /**
   * New instance of Schema provider.
   *
   * @codeCoverageIgnore
   * @return Provider
   *   Provider instance.
   */
  protected function getSchemaProvider() {
    $schmaRetriever = \Drupal::service('dkan_schema.schema_retriever');
    return new Provider($schmaRetriever);
  }

  /**
   * @todo refactor to not use static call
   * @param string $uuid
   * @return \Dkan\Datastore\Manager\IManager
   */
  protected function getDatastoreManager($uuid) {
    return Util::getDatastoreManager($uuid);
  }

}
