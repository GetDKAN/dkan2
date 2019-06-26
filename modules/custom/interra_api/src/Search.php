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
   * Indexes the available datasets.
   */
  public function index() {
    // TODO: Make this configurable.
    $build = new BuildLunrIndex();
    $build->ref('identifier');
    $build->field("title");
    $build->field("description");
    $build->field("theme");
    $build->field("keyword");

    $build->addPipeline('LunrPHP\LunrDefaultPipelines::trimmer');
    $build->addPipeline('LunrPHP\LunrDefaultPipelines::stop_word_filter');
    $build->addPipeline('LunrPHP\LunrDefaultPipelines::stemmer');

    $datasets = $this->getDatasets();

    foreach ($datasets as $dataset) {
      $build->add((array)$dataset);
    }

    return $build->output();
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
