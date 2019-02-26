<?php

namespace Drupal\dkan_harvest\Commands;

use Drupal\dkan_harvest\EtlWorkerFactory;
use Drupal\dkan_harvest\Log\Stdout;
use Drupal\dkan_harvest\Reverter;
use Drupal\dkan_harvest\Storage\IdGenerator;
use Drush\Commands\DrushCommands;
use Drush\Style\DrushStyle;
use Drupal\dkan_harvest\Harvester;
use Drupal\dkan_harvest\Storage\Source;
use Sae\Sae;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

class DkanHarvestCommands extends DrushCommands {

  /**
   * Lists avaialble harvests.
   *
   * @command dkan-harvest:list
   *
   * @usage dkan-harvest:list
   *   List available harvests.
   */
  public function index() {
    $source = new Source();
    $items = $source->index();

    $rows = [];

    if (isset($items['source_id'])) {
      foreach ($items['source_id'] as $item) {
        $rows[$item][] = $item;
      }
    }

    $table = new Table(new ConsoleOutput());

    $table
      ->setHeaders(array('source id'))
      ->setRows($rows);

    $table->render();
  }

  /**
   * Register a new harvest.
   *
   * @command dkan-harvest:register
   */
  public function register($config) {
    $source = new Source();
    $schema_path = DRUPAL_ROOT . "/" . drupal_get_path("module", "dkan_harvest") . "/schema/schema.json";
    $schema = file_get_contents($schema_path);
    $engine = new Sae($source, $schema);
    $engine->setIdGenerator(new IdGenerator($config));
    $engine->post($config);
  }

  /**
   * Deregister a harvest.
   *
   * @command dkan-harvest:deregister
   */
  public function deregister($id) {
    $source = new Source();
    $schema_path = DRUPAL_ROOT . "/" . drupal_get_path("module", "dkan_harvest") . "/schema/schema.json";
    $schema = file_get_contents($schema_path);
    $engine = new Sae($source, $schema);
    $engine->delete($id);
  }

  /**
   * Caches harvest.
   *
   * @param string $sourceId
   *   The source to cache.
   *
   * @command dkan-harvest:cache
   *
   * @usage dkan-harvest:cache
   *   Cache harvest source.
   */
  public function cache($sourceId) {
    $harvest_plan = $this->getHarvestPlan($sourceId);
    $harvest_plan->runId = 'cache';

    $factory = new EtlWorkerFactory($harvest_plan);

    /* @var $extract \Drupal\dkan_harvest\Extract\Extract */
    $extract = $factory->get('extract');
    $extract->setLogger(new Stdout(true, $harvest_plan->sourceId,"cache"));
    $extract->cache();
  }

  /**
   * Runs harvest.
   *
   * @param string $sourceId
   *   The source to run.
   *
   * @command dkan-harvest:run
   *
   * @usage dkan-harvest:run
   *   Runs a harvest from the harvest source.
   */
  public function run($sourceId) {
    $harvest_plan = $this->getHarvestPlan($sourceId);

    $harvester = new Harvester($harvest_plan);
    $harvester->setLogger(new Stdout(true, $sourceId,"run"));

    $results = $harvester->harvest();

    $rows = [];
    $rows[] = [$results['created'], $results['updated'], $results['skipped']];


    $table = new Table(new ConsoleOutput());
    $table->setHeaders(['created', 'updated', 'skipped'])->setRows($rows);
    $table->render();
  }

  /**
   * Reverts harvest.
   *
   * @param string $sourceId
   *   The source to revert.
   *
   * @command dkan-harvest:revert
   *
   * @usage dkan-harvest:revert
   *   Removes harvested entities.
   */
  public function revert($sourceId) {
    $reverter = new Reverter($sourceId);
    $count = $reverter->run();

    $output = new ConsoleOutput();
    $output->write("{$count} items reverted for the '{$sourceId}' harvest plan.");
  }

  private function getHarvestPlan($sourceId) {
    $source = new Source();
    return json_decode($source->retrieve($sourceId));
  }
}

