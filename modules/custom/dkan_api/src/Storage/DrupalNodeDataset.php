<?php

namespace Drupal\dkan_api\Storage;

use Harvest\Storage\Storage;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * DrupalNodeDataset.
 */
class DrupalNodeDataset implements Storage {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Represents the data type passed via the HTTP request url schema_id slug.
   *
   * @var string
   */
  protected $schemaId;

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
   * Sets the data type.
   *
   * @param $schema_id string
   *   The HTTP request's schema or data type.
   */
  public function setSchema($schema_id) {
    $this->schemaId = $schema_id;
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
      ->condition('field_data_type', $this->schemaId)
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

    if (!$id && isset($data->identifier)) {
      $id = $data->identifier;
    }

    if ($id) {
      $node = $this->getNodeByUuid($id);
    }

    /* @var $node \Drupal\node\NodeInterface */
    // update existing node
    if ($node) {
      $node->field_data_type = $this->schemaId;
      $new_data = json_encode($data);
      $node->field_json_metadata = $new_data;
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
          'field_data_type' => $this->schemaId,
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

}
