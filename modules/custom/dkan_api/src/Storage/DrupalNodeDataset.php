<?php

namespace Drupal\dkan_api\Storage;

use Drupal\dkan_common\Storage\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * DrupalNodeDataset.
 */
class DrupalNodeDataset implements StorageInterface {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Injected entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Get the node storage.
   *
   * @return \Drupal\node\NodeStorageInterface
   *   Node Storage.
   */
  protected function getNodeStorage() {
    return $this->entityTypeManager
      ->getStorage('node');
  }

  /**
   * @return string
   *   Type of node.
   */

  /**
   * @var Drupal\dkan_api\Storage\ThemeValueReferencer
   */
  private $themeValueReferencer;

  /**
   * Constructs a DrupalNodeDataset.
   */
  public function __construct() {
    $this->themeValueReferencer = new ThemeValueReferencer();
  }

  protected function getType() {
    return 'data';
  }

  /**
   * {@inheritDoc}.
   */
  public function retrieve(string $id): ?string {

    if (FALSE !== ($node = $this->getNodeByUuid($id))) {
      return $node->field_json_metadata->value;
    }

    throw new \Exception("No data with the identifier {$id} was found.");
  }

  /**
   * {@inheritDoc}.
   */
  public function retrieveAll(): array {

    $nodeStorage = $this->getNodeStorage();

    $node_ids = $nodeStorage->getQuery()
      ->condition('type', $this->getType())
      ->execute();

    $all = [];
    foreach ($node_ids as $nid) {
      $node = $nodeStorage->load($nid);
      $all[] = $node->field_json_metadata->value;
    }
    return $all;
  }

  /**
   * {@inheritDoc}.
   */
  public function remove(string $id) {

    if (FALSE !== ($node = $this->getNodeByUuid($id))) {
      return $node->delete();
    }
  }

  /**
   * {@inheritDoc}.
   */
  public function store(string $data, string $id = NULL): string {

    $data = json_decode($data);

    if (isset($data->theme)) {
      $data->theme = $this->themeValueReferencer->reference($data);
    }

    if (!$id && isset($data->identifier)) {
      $id = $data->identifier;
    }

    if ($id) {
      $node = $this->getNodeByUuid($id);
    }

    /* @var $node \Drupal\node\NodeInterface */
    // update existing node
    if ($node) {
      $node->field_data_type = "dataset";
      $node->field_json_metadata = json_encode($data);
      $node->save();
      return $node->uuid();
    }
    // Create new node.
    else {
      $title = isset($data->title) ? $data->title : $data->name;
      $node = $this->getNodeStorage()
        ->create([
          'title' => $title,
          'type' => 'data',
          'uuid' => $id,
          'field_data_type' => 'dataset',
          'field_json_metadata' => json_encode($data),
        ]);
      $node->save();
      return $node->uuid();
    }

    return NULL;
  }

  /**
   * Fetch node id of a current type given uuid.
   *
   * @return \Drupal\node\Entity\Node|bool
   *   Returns false if no nodes match.
   */
  protected function getNodeByUuid($uuid) {

    $nodes = $this->getNodeStorage()
      ->loadByProperties([
        'type' => $this->getType(),
        'uuid' => $uuid,
      ]);
    // Uuid should be universally unique and always return
    // a single node.
    return current($nodes);
  }

  protected function themeDereferenced($json) {
    $data = json_decode($json);
    if (isset($data->theme)) {
      $data->theme = $this->themeValueReferencer->dereference($data);
    }
    return json_encode($data);
  }

}
