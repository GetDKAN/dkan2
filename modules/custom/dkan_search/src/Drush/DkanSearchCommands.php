<?php

namespace Drupal\dkan_search\Drush;

use Drush\Commands\DrushCommands;
use Drupal\search_api\Entity\Index;

class DkanSearchCommands extends DrushCommands {

  /**
   * Rebuild the search api tracker for the dkan index.
   *
   * @command dkan-search:rebuild-tracker
   */
  public function reindex() {
    $index = Index::load('dkan');
    $index->rebuildTracker();
  }

}
