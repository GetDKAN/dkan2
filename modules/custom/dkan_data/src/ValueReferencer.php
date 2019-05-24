<?php

declare(strict_types = 1);

namespace Drupal\dkan_data;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Queue\QueueFactory;
use stdClass;

/**
 * Replaces some dataset property values with references, or vice versa.
 *
 * @package Drupal\dkan_api\Storage
 */
class ValueReferencer {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The uuid service.
   *
   * @var Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * The queue service.
   *
   * @var Drupal\Core\Queue\QueueFactory
   */
  protected $queueService;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Injected entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, UuidInterface $uuidService, QueueFactory $queueService) {
    $this->entityTypeManager = $entityTypeManager;
    $this->uuidService = $uuidService;
    $this->queueService = $queueService;
  }

  /**
   * Replaces some dataset property values with references.
   *
   * @param stdClass $data
   *   Json object from field_json_metadata.
   *
   * @return stdClass
   *   Modified json object.
   */
  public function reference(stdClass $data) {
    // Cycle through the dataset properties we seek to reference.
    foreach ($this->getPropertyList() as $property_id) {
      if (isset($data->{$property_id})) {
        $data->{$property_id} = $this->referenceProperty($property_id, $data->{$property_id});
      }
    }
    return $data;
  }

  /**
   * @param string $property_id
   * @param mixed $data
   *
   * @return mixed
   */
  protected function referenceProperty(string $property_id, $data) {
    if (is_array($data)) {
      return $this->referenceMultiple($property_id, $data);
    }
    if (is_object($data) || is_string($data)) {
      return $this->referenceSingle($property_id, $data);
    }
  }

  protected function referenceMultiple(string $property_id, array $values) : array {
    $result = [];
    foreach ($values as $value) {
      $result[] = $this->referenceSingle($property_id, $value);
    }
    return $result;
  }

  protected function referenceSingle(string $property_id, $value) {
    $uuid = $this->checkExistingReference($property_id, $value);
    if (!$uuid) {
      $uuid = $this->createPropertyReference($property_id, $value);
    }
    if ($uuid) {
      return $uuid;
    }
    else {
      return $value;
    }
  }

  protected function checkExistingReference(string $property_id, $data) {
    $nodes = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties([
        'field_data_type' => $property_id,
        'title' => $this->setReferenceTitle($data),
      ]);

    if ($node = reset($nodes)) {
      return $node->uuid->value;
    }
    return NULL;
  }

  protected function createPropertyReference(string $property_id, $data) {
    $today = date('Y-m-d');

    // Create json metadata for the reference.
    $ref = new stdClass();
    $ref->identifier = $this->uuidService->generate();
    $ref->data = $data;
    $ref->created = $today;
    $ref->modified = $today;

    // Create node to store this reference.
    $node = $this->entityTypeManager
      ->getStorage('node')
      ->create([
        'title' => $this->setReferenceTitle($data),
        'type' => 'data',
        'uuid' => $ref->identifier,
        'field_data_type' => $property_id,
        'field_json_metadata' => json_encode($ref),
      ]);
    $node->save();

    return $node->uuid();
  }

  protected function setReferenceTitle($data) {
    if (is_string($data)) {
      return $data;
    }
    else {
      return md5(json_encode($data));
    }
  }

  /**
   * Returns the human-readable theme values from uuids.
   *
   * @param \stdClass $data
   *   The object from the json data string.
   *
   * @return mixed
   *   An array of theme values, or NULL.
   */
  public function dereference(stdClass $data) {
    // Cycle through the dataset properties we seek to dereference.
    foreach ($this->getPropertyList() as $property_id) {
      if (isset($data->{$property_id})) {
        $data->{$property_id} = $this->dereferenceProperty($property_id, $data->{$property_id});
      }
    }
    return $data;
  }

  protected function dereferenceProperty(string $property_id, $data) {
    if (is_array($data)) {
      return $this->dereferenceMultiple($property_id, $data);
    }
    else
      return $this->dereferenceSingle($property_id, $data);
  }

  protected function dereferenceMultiple(string $property_id, array $data) : array {
    $result = [];
    foreach ($data as $datum) {
      $result[] = $this->dereferenceSingle($property_id, $datum);
    }
    return $result;
  }

  /**
   * Returns the human-readable theme value from its uuid.
   *
   * @param string $str
   *   The string could either be a uuid or a human-readable theme value.
   *
   * @return string
   *   The theme value.
   */
  protected function dereferenceSingle(string $property_id, string $str) {
    $nodes = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties([
        'field_data_type' => $property_id,
        'uuid' => $str,
      ]);
    if ($node = reset($nodes)) {
      if (isset($node->field_json_metadata->value)) {
        $metadata = json_decode($node->field_json_metadata->value);
        return $metadata->data;
      }
    }
    return $str;
  }

  /**
   * Queue deleted themes for processing, as they may be orphans.
   *
   * @param string $old
   *   Json string of item being replaced.
   * @param string $new
   *   Json string of item doing the replacing.
   */
  public function processDeletedReferences(string $old, $new = "{}") {
    $themes_removed = $this->referencesRemoved($old, $new);

    $orphan_theme_queue = $this->queueService->get('orphan_property_processor');
    foreach ($themes_removed as $theme_removed) {
      // @Todo: Only add to the queue when uuid doesn't already exists in it.
      $orphan_theme_queue->createItem($theme_removed);
    }
  }

  /**
   * Returns an array of theme uuid(s) being removed as the data changes.
   *
   * @param string $old
   *   Json string of item being replaced.
   * @param string $new
   *   Json string of item doing the replacing.
   *
   * @return array
   *   Array of theme uuid(s).
   */
  public function referencesRemoved($old, $new = "{}") {
    $old_data = json_decode($old);
    if (!isset($old_data->theme)) {
      // No theme to potentially delete nor check for orphan.
      return [];
    }
    $old_themes = $old_data->theme;

    $new_data = json_decode($new);
    if (!isset($new_data->theme)) {
      $new_themes = [];
    }
    else {
      $new_themes = $new_data->theme;
    }

    return array_diff($old_themes, $new_themes);
  }

  /**
   * Get the list of dataset properties being referenced.
   *
   * @return array
   *   list of dataset properties.
   */
  protected function getPropertyList() {
    $config = \Drupal::config('dkan_data.settings');
    $config_property_list = trim($config->get('property_list'));

    // Trim and split list on newlines whether Windows, MacOS or Linux.
    $property_list = preg_split(
      '/\s*\r\n\s*|\s*\r\s*|\s*\n\s*/',
      $config_property_list,
      -1,
      PREG_SPLIT_NO_EMPTY
    );
    return $property_list;
  }

}
