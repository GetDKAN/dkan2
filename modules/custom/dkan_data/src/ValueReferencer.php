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
   * @param \Drupal\Component\Uuid\UuidInterface $uuidService
   *   Injected uuid service.
   * @param \Drupal\Core\Queue\QueueFactory $queueService
   *   Injected queue service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, UuidInterface $uuidService, QueueFactory $queueService) {
    $this->entityTypeManager = $entityTypeManager;
    $this->uuidService = $uuidService;
    $this->queueService = $queueService;
  }

  /**
   * Replaces some dataset property values with references.
   *
   * @param \stdClass $data
   *   Json object from field_json_metadata.
   *
   * @return \stdClass
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
   * Replaces a single property's data with a reference.
   *
   * @param string $property_id
   *   The dataset property id.
   * @param mixed $data
   *   Single reference, or an array of references.
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

  /**
   * References the values in an array.
   *
   * @param string $property_id
   *   The dataset property id.
   * @param array $values
   *   Array of values to reference.
   *
   * @return array
   *   Array of references.
   */
  protected function referenceMultiple(string $property_id, array $values) : array {
    $result = [];
    foreach ($values as $value) {
      $result[] = $this->referenceSingle($property_id, $value);
    }
    return $result;
  }

  /**
   * References when the property's value is a string or object.
   *
   * @param string $property_id
   *   The dataset property id.
   * @param $value
   *   The dataset json value of that particular property.
   *
   * @return string|null
   */
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

  /**
   * Checks for existing reference by hashing its value.
   *
   * @param string $property_id
   *   The dataset property id.
   * @param $data
   *
   * @return string|null
   */
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

  /**
   * Creates
   *
   * @param string $property_id
   *   The dataset property id.
   * @param mixed $data
   *
   * @return string|null
   */
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

  /**
   * Create a simple hash of the json data in string format.
   *
   * @param string|stdClass $data
   *   The json value of a particular dataset property.
   *
   * @return string
   *   Md5 hash.
   */
  protected function setReferenceTitle($data) {
    return md5(json_encode($data));
  }

  /**
   * Replaces references with their values in an entire dataset.
   *
   * @param \stdClass $data
   *   The json metadata object.
   *
   * @return mixed
   *   Modified json metadata object.
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

  /**
   * @param string $property_id
   *   The dataset property id.
   * @param $data
   *   An array or string of uuid's.
   *
   * @return array|string
   *   An array or string of dereferenced values.
   */
  protected function dereferenceProperty(string $property_id, $data) {
    if (is_array($data)) {
      return $this->dereferenceMultiple($property_id, $data);
    }
    else {
      return $this->dereferenceSingle($property_id, $data);
    }
  }

  /**
   * Process the dereferencing of the items in an array.
   *
   * @param string $property_id
   *   A dataset property id.
   * @param array $data
   *   An array of references.
   *
   * @return array
   *   An array of unreferenced values.
   */
  protected function dereferenceMultiple(string $property_id, array $data) : array {
    $result = [];
    foreach ($data as $datum) {
      $result[] = $this->dereferenceSingle($property_id, $datum);
    }
    return $result;
  }

  /**
   * Returns the string or object value of a reference.
   *
   * @param string $property_id
   *   The dataset property id.
   * @param string $str
   *   Either a uuid or an actual json value.
   *
   * @return string
   *   The data from this reference.
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
    // If str was not found, it's unlikely it was a uuid to begin with. It was
    // most likely never referenced to begin with, so return unchanged.
    return $str;
  }

  /**
   * Queue deleted references for processing, as they may be orphans.
   *
   * @param string $old
   *   Json string of item being replaced.
   * @param string $new
   *   Json string of item doing the replacing.
   */
  public function processDeletedReferences(string $old, $new = "{}") {
    $references_removed = $this->referencesRemoved($old, $new);

    $orphan_reference_queue = $this->queueService->get('orphan_property_processor');
    foreach ($references_removed as $reference_removed) {
      // @Todo: Only add to the queue when uuid doesn't already exists in it.
      $orphan_reference_queue->createItem($reference_removed);
    }
  }

  /**
   * Returns an array of references being removed as the data changes.
   *
   * @param string $old
   *   Json string of item being replaced.
   * @param string $new
   *   Json string of item doing the replacing.
   *
   * @return array
   *   Array of references.
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
   *   List of dataset properties.
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
