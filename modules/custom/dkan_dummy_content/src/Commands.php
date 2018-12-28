<?php

namespace Drupal\dkan_dummy_content;

use Drush\Commands\DrushCommands;
use GuzzleHttp\Client;

class Commands extends DrushCommands {

  /**
   * Import dummy content from a file to the site.
   *
   * @command dkan-dummy-content:import
   *
   * @usage dkan-dummy-content:import
   *   Import dummy content from a file to the site.
   */
  public function import() {
    $path_public_files = \Drupal::service('file_system')
      ->realpath(file_default_scheme() . "://");

    $dummy_content_file = "$path_public_files/dkan_dummy_content.json";

    if (file_exists($dummy_content_file)) {
      $content = file_get_contents($dummy_content_file);
      $phpized = json_decode($content);
      $client = new Client();

      $url = "http://web/api/v1/dataset";

      foreach ($phpized as $dataset) {
        $response = $client->post($url, [
          \GuzzleHttp\RequestOptions::JSON => $dataset
        ]);
        $this->io()->note("{$dataset->title}: {$response->getStatusCode()}");
      }
    }
    else {
      $this->io()->error("The file {$dummy_content_file} was not found.");
    }
  }
}

