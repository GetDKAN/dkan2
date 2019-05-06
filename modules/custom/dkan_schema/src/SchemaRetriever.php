<?php

namespace Drupal\dkan_schema;

use Contracts\Retriever;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\SitePathFactory;

class SchemaRetriever implements Retriever {

  /**
   *
   * @var string
   */
  protected $directory;

  public function __construct() {
    $this->findSchemaDirectory();
  }

  public function getAllIds() {
    return [
        'dataset'
    ];
  }

  public function getSchemaDirectory() {
    return $this->directory;
  }

  public function retrieve(string $id): ?string {

    $filename = $this->getDirectory() . "/collections/{$id}.json";

    if (
            in_array($id, $this->getAllIds())
            && is_readable($filename)
        ) {
      return file_get_contents($filename);
    }
    throw new \Exception("Schema {$id} not found.");
  }

  protected function findSchemaDirectory() {

    $drupalRoot = \Drupal::service('app.root');

    if (is_dir($drupalRoot . "/schema")) {
      $this->directory = $drupalRoot . "/schema";
    } elseif (
      ($directory = $this->getDefaultSchemaDirectory())
       && is_dir($directory)
    ) {
      $this->directory = $directory;
    } else {
      throw new \Exception("No schema directory found.");
    }
  }

  /**
   * determine from root dir of dkan2 profile.
   * @return string path.
   */
  protected function getDefaultSchemaDirectory() {
    return dirnname(drupal_get_filename('profile', 'dkan2')) . '/schema';
  }

}
