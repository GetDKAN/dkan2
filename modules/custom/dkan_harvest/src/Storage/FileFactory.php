<?php


namespace Drupal\dkan_harvest\Storage;


use Contracts\FactoryInterface;
use Drupal\Core\File\FileSystemInterface;

class FileFactory implements FactoryInterface {

  private $stores = [];
  private $fileSystem;

  public function __construct(FileSystemInterface $fileSystem) {
    $this->fileSystem = $fileSystem;
  }

  public function getInstance(string $identifier) {
    if (!isset($this->stores[$identifier])) {
      $public_directory = $this->fileSystem->realpath("public://");
      $harvest_config_directory = $public_directory . "/dkan_harvest";
      $this->stores[$identifier] = new File($harvest_config_directory);
    }
    return $this->stores[$identifier];
  }

}
