<?php

namespace Drupal\dkan_dummy_content;

use Drupal\dkan_harvest\Harvester;
use Drupal\dkan_harvest\Log\Stdout;
use Drupal\dkan_harvest\Reverter;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Helper\Table;

class Commands extends DrushCommands {

  /**
   * Create dummy content.
   *
   * @command dkan-dummy-content:create
   *
   */
  public function create() {

    $harvest_plan = <<<JSON
{
  "sourceId": "dummy", 
  "source": {
    "type": "DataJson", 
    "uri": "http://demo.getdkan.com/data.json"
  }, 
  "transforms": [], 
  "load": {
    "migrate": false, 
    "collectionsToUpdate": ["dataset"], "type": "Dataset"
  }
}
JSON;


    $harvester = new Harvester(json_decode($harvest_plan));
    $harvester->setLogger(new Stdout(true, "dummy", "run"));

    $results = $harvester->harvest();

    $rows = [];
    $rows[] = [$results['created'], $results['updated'], $results['skipped']];


    $table = new Table(new ConsoleOutput());
    $table->setHeaders(['created', 'updated', 'skipped'])->setRows($rows);
    $table->render();
  }

  /**
   * Remove dummy content.
   *
   * @command dkan-dummy-content:remove
   */
  public function remove() {
    $reverter = new Reverter("dummy");
    $count = $reverter->run();

    $output = new ConsoleOutput();
    $output->write("{$count} items reverted for the 'dummy' harvest plan.");
  }
}

