<?php
/**
 * @file
 * Creates search index using Lunr.php.
 */

namespace Drupal\interra_api;

use LunrPHP\Pipeline;
use LunrPHP\LunrDefaultPipelines;
use LunrPHP\BuildLunrIndex;


/**
 * Indexes datasets using Lunr.php.
 * @codeCoverageIgnore
 */
class Search {


  /**
   * Fields to be searched for in the Lunr index. The more fields added the
   * bigger the index.
   *
   * TODO: Make configurable.
   */
  public $searchIndexFields = [
    "title",
    "description"
  ];

  /**
   * Fields to be available in search results. The more fields added the
   * bigger the index.
   *
   * TODO: Make configurable.
   */
  public $searchDocFields = [
    "title",
    "identifier",
    "description",
    "modified",
    "distribution",
    "keyword",
    "theme"
  ];

  public $ref = "identifier";

	public function formatDocs($docs) {
    $index = [];
    foreach ($docs as $id => $doc) {
      $index[] = $this->formatSearchDoc($doc);
    }
    return $index;
  }

  /**
   *
   */
  public function formatSearchDoc($value) {
    $formatted      = new \stdClass();
    $doc      = new \stdClass();
    foreach ($this->searchDocFields as $field) {
      $doc->{$field} = isset($value->{$field}) ? $value->{$field} : null;
    }
    $formatted->doc = $doc;
    $formatted->ref = $doc->{$this->ref};
    return $formatted;
  }


	public function lunrIndex() {
    // TODO: Make this configurable.
    $build = new BuildLunrIndex();
    $build->ref($this->ref);
    foreach($this->searchIndexFields as $field) {
      $build->field($field);
    }

    $build->addPipeline('LunrPHP\LunrDefaultPipelines::trimmer');
    $build->addPipeline('LunrPHP\LunrDefaultPipelines::stop_word_filter');
    $build->addPipeline('LunrPHP\LunrDefaultPipelines::stemmer');

    $datasets = $this->getDatasets();

    foreach ($datasets as $dataset) {
      $build->add((array)$dataset);
    }

    return $build->output();
	}

	public function docs() {
	  $datasets = [];
    /** @var Service\DatasetModifier $dataset_modifier */
    $dataset_modifier = \Drupal::service('interra_api.service.dataset_modifier');
    foreach ($this->getDatasets() as $dataset) {
      $datasets[] = $dataset_modifier->modifyDataset($dataset);
    }
    return $this->formatDocs($datasets);
  }


  /**
   * Indexes the available datasets.
   */
  public function index() {
    return [
      'index' => $this->lunrIndex(),
      'docs' => $this->docs()
    ];
  }

  /**
   * Get datasets.
   *
   * @TODO Shouldn't use controller inner workings like this. Should refactor to service.
   *
   * @return array Array of dataset objects
   */
  protected function getDatasets() {
    /** @var \Drupal\dkan_api\Controller\Dataset $dataset_controller */
    $dataset_controller = \Drupal::service('dkan_api.controller.dataset');

    // Engine returns array of json strings.
    return array_map(
            function ($item) {
              return json_decode($item);
            },
            $dataset_controller->getEngine()
              ->get()
    );
  }


}
