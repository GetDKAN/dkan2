<?php

namespace Drupal\dkan_frontend\Controller;

use Drupal\dkan_frontend\Page as PageBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * An ample controller.
 */
class Page implements ContainerInjectionInterface {

  private $pageBuilder;

  /**
   * Inherited.
   *
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new Page($container->get('dkan_frontend.page'));
  }

  /**
   * Constructor.
   */
  public function __construct(PageBuilder $pageBuilder) {
    $this->pageBuilder = $pageBuilder;
  }

  /**
   * Controller method.
   */
  public function page($name) {
    $pageContent = $this->pageBuilder->build($name);
    if (empty($pageContent)) {
      $pageContent = $this->pageBuilder->build("404");
    }
    return Response::create($pageContent);
  }

  /**
   * Controller method.
   */
  public function dataset($name) {
    $node_loaded_by_uuid = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['uuid' => $name]);
    $node_loaded_by_uuid = reset($node_loaded_by_uuid);
    $pageContent = $this->pageBuilder->build('dataset');
    if ($node_loaded_by_uuid) {
      $pageContent = $this->pageBuilder->build("dataset/" . $name);
      if (empty($pageContent)) {
        $pageContent = $this->pageBuilder->build('dataset');
      }
    }

    return Response::create($pageContent);
  }

}
