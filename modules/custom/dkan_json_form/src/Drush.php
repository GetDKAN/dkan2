<?php

namespace Drupal\dkan_json_form;

use Drush\Commands\DrushCommands;
use Masterminds\HTML5;
use Symfony\Component\Yaml\Yaml;
use DOMElement;

/**
 * Drush commands.
 *
 * @codeCoverageIgnore
 */
class Drush extends DrushCommands {

  private $moduleDirectory;
  private $librariesFilePath;
  private $reactAppPath;
  private $reactAppBuildDirectoryPath;
  private $reactAppBuildStaticJsDirectoryPath;
  private $reactAppBuildStaticCssDirectoryPath;

  public function __construct()
  {
    $this->moduleDirectory = drupal_get_path("module", "dkan_json_form");
    $this->librariesFilePath = $this->moduleDirectory . "/dkan_json_form.libraries.yml";
    $this->reactAppPath = $this->moduleDirectory . "/js/app";
    $this->reactAppBuildDirectoryPath = $this->reactAppPath . "/build";
    $this->reactAppBuildStaticJsDirectoryPath = $this->reactAppBuildDirectoryPath . "/static/js";
    $this->reactAppBuildStaticCssDirectoryPath = $this->reactAppBuildDirectoryPath . "/static/css";
  }

  /**
   * Sync.
   *
   * Synchronize the module with the React app.
   *
   * @command dkan-json-form:sync
   */
  public function sync() {
    $this->createLoadMeJs();
    $this->createtLibrariesFile();
  }

  private function createtLibrariesFile() {

    if (file_exists($this->librariesFilePath)) {
      unlink($this->librariesFilePath);
    }

    /*  "js/app/build/static/js/{$chunks[0]}" => [],
        "js/app/build/static/js/{$chunks[1]}" => [],*/

    $libraries = ['dkan_json_form' => [
      "version" => "1.x",
      "js" => [
        "js/app/build/static/js/loadme.js" => [],
      ],
      "css" => [
        "base" => []
      ],
      "dependencies" => [
        "core/drupalSettings"
      ]
    ]];

    $paths = [
      "css" => $this->reactAppBuildStaticCssDirectoryPath,
      "js" => $this->reactAppBuildStaticJsDirectoryPath
    ];

    foreach ($paths as $type => $path) {
      $base = "js/app/build/static/{$type}/";

      $skips = ["LICENSE", 'map', 'loadme', 'runtime'];
      $folderInfo = scandir($path);
      unset($folderInfo[0]);
      unset($folderInfo[1]);
      $chunks = [];
      foreach ($folderInfo as $dirfile) {
        $skip = false;
        foreach ($skips as $s) {
          if (substr_count($dirfile, $s) > 0) {
            $skip = true;
            break;
          }
        }
        if (!$skip) {
          $chunks[] = $dirfile;
        }
      }
      foreach ($chunks as $chunk) {
        if ($type == 'js') {
            $libraries['dkan_json_form']['js'][$base . $chunk] = [];
        }
        else {
          $libraries['dkan_json_form']['css']['base'][$base . $chunk] = [];
        }
      }
    }

    $yaml = Yaml::dump($libraries);
    file_put_contents($this->librariesFilePath, $yaml);
  }

  private function createLoadMeJs() {
    $loadMeJsFilePath = $this->reactAppBuildStaticJsDirectoryPath . "/loadme.js";

    if (file_exists($loadMeJsFilePath)) {
      unlink($loadMeJsFilePath);
    }

    $indexFilePath = $this->reactAppBuildDirectoryPath . "/index.html";

    $html = new HTML5();
    $document = $html->parse(file_get_contents($indexFilePath));
    $scriptTags = $document->getElementsByTagName("script");

    /* @var $scriptTag DOMElement */
    foreach ($scriptTags as $scriptTag) {
      $content = $scriptTag->textContent;
      if (!empty($content)) {
        file_put_contents($loadMeJsFilePath, $content);
      }
    }
  }
}
