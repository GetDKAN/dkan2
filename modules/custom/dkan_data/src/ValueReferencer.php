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
   * @param stdClass $data
   *   Dataset json object.
   *
   * @return stdClass
   *   Json object modified with references to some of its properties' values.
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
   * References a dataset property's value, general case.
   *
   * @param string $property_id
   *   The dataset property id.
   * @param mixed $data
   *   Single value or array of values to be referenced.
   *
   * @return string|array
   *   Single reference, or an array of references.
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
   * References a dataset property's value, array case.
   *
   * @param string $property_id
   *   The dataset property id.
   * @param array $values
   *   The array of values to be referenced.
   *
   * @return array
   *   The array of uuid references.
   */
  protected function referenceMultiple(string $property_id, array $values) : array {
    $result = [];
    foreach ($values as $value) {
      $result[] = $this->referenceSingle($property_id, $value);
    }
    return $result;
  }

  /**
   * References a dataset property's value, string or object case.
   *
   * @param string $property_id
   *   The dataset property id.
   * @param string|stdClass $value
   *   The value to be referenced.
   *
   * @return string
   *   The Uuid reference, or unchanged value.
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
      // In the unlikely case we neither found an existing reference nor could
      // create a new reference, return the unchanged value.
      return $value;
    }
  }

  /**
   * Checks for an existing value reference for that property id.
   *
   * @param string $property_id
   *   The dataset property id.
   * @param $data
   *   The property's value used to find an existing reference.
   *
   * @return string|null
   *   The existing reference's uuid, or null if not found.
   */
  protected function checkExistingReference(string $property_id, $data) {
    $nodes = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties([
        'field_data_type' => $property_id,
        'title' => $this->titleHash($data),
      ]);

    if ($node = reset($nodes)) {
      return $node->uuid->value;
    }
    return NULL;
  }

  /**
   * Creates a new value reference for that property id in a data node.
   *
   * @param string $property_id
   *   The dataset property id.
   * @param mixed $data
   *   The property's value.
   *
   * @return string|null
   *   The new reference's uuid, or null.
   */
  protected function createPropertyReference(string $property_id, $data) {
    // Create json metadata for the reference.
    $ref = new stdClass();
    $ref->identifier = $this->uuidService->generate();
    $ref->data = $data;

    // Create node to store this reference.
    $node = $this->entityTypeManager
      ->getStorage('node')
      ->create([
        'title' => $this->titleHash($data),
        'type' => 'data',
        'uuid' => $ref->identifier,
        'field_data_type' => $property_id,
        'field_json_metadata' => json_encode($ref),
      ]);
    $node->save();

    return $node->uuid();
  }

  /**
   * Hashes a property's value, to use as a title and for easier comparison.
   *
   * @param string|stdClass $data
   *   The dataset property value.
   *
   * @return string
   *   Md5 hash.
   */
  protected function titleHash($data) {
    return md5(json_encode($data));
  }

  /**
   * Replaces value references in a dataset with with their actual values.
   *
   * @param stdClass $data
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
   * Replaces a property reference with its actual value, general case.
   *
   * @param string $property_id
   *   The dataset property id.
   * @param $data
   *   An array or string of reference uuids.
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
   * Replaces a property reference with its actual value, array case.
   *
   * @param string $property_id
   *   A dataset property id.
   * @param array $data
   *   An array of reference uuids.
   *
   * @return array
   *   An array of dereferenced values.
   */
  protected function dereferenceMultiple(string $property_id, array $data) : array {
    $result = [];
    foreach ($data as $datum) {
      $result[] = $this->dereferenceSingle($property_id, $datum);
    }
    return $result;
  }

  /**
   * Replaces a property reference with its actual value, string or object case.
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
   * Check for orphan references when a dataset is being deleted.
   *
   * @param stdClass $data
   *   Dataset to be deleted.
   */
  public function processReferencesInDeletedDataset(stdClass $data) {
    // Cycle through the dataset properties we seek to reference.
    foreach ($this->getPropertyList() as $property_id) {
      if (isset($data->{$property_id})) {
        $this->processReferencesInDeletedProperty($property_id, $data->{$property_id});
      }
    }
  }

  protected function processReferencesInDeletedProperty($property_id, $uuids) {
    // Treat single uuid as an array of one uuid.
    if (!is_array($uuids)) {
      $uuids = [$uuids];
    }
    foreach ($uuids as $uuid) {
      $this->queueReferenceForRemoval($property_id, $uuid);
    }
  }

  protected function queueReferenceForRemoval($property_id, $uuid) {
    $this->queueService->get('orphan_property_processor')
      ->createItem([
        $property_id,
        $uuid,
      ]);
  }

  public function processReferencesInUpdatedDataset(stdClass $old_dataset, stdClass $new_dataset) {
    // Cycle through the dataset properties being referenced, check for orphans.
    foreach ($this->getPropertyList() as $property_id) {
      if (!isset($old_dataset->{$property_id})) {
        // The old dataset had no value for this property, thus no references
        // could be deleted. Safe to skip checking for orphan reference.
        continue;
      }
      if (!isset($new_dataset->{$property_id})) {
        $new_dataset->{$property_id} = $this->emptyPropertyOfSameType($old_dataset->{$property_id});
      }
      $this->processReferencesInUpdatedProperty($property_id, $old_dataset->{$property_id}, $new_dataset->{$property_id});
    }
  }

  protected function processReferencesInUpdatedProperty($property_id, $old_value, $new_value) {
    if (!is_array($old_value)) {
      $old_value = [$old_value];
      $new_value = [$new_value];
    }
    foreach (array_diff($old_value, $new_value) as $removed_reference) {
      $this->queueReferenceForRemoval($property_id, $removed_reference);
    }
  }

  protected function emptyPropertyOfSameType($data) {
    switch (gettype($data)) {
      case 'array':
        return [];
      case 'string':
        return "";
      case 'object':
        return (object) [];
    }
  }



  /**
   * Check for orphan references when a dataset is being updated.
   *
   * @param stdClass $old_data
   *   The original dataset.
   * @param stdClass $new_data
   *   The updated dataset.
   */
//  public function processUpdatedDataset(stdClass $old_data, stdClass $new_data) {
//    ddl($old_dataset, 'old dataset');
//    ddl($new_dataset, 'new dataset');
    // Cycle through the dataset properties being referenced.
//    foreach ($this->getPropertyList() as $property_id) {
//      if (isset($data->{$property_id})) {
//        $data->{$property_id} = $this->referenceProperty($property_id, $data->{$property_id});
//      }
//    }
//  }

  /**
   * Queue deleted references for processing, as they may be orphans.
   *
   * @param string $old
   *   Json string of item being replaced.
   * @param string $new
   *   Json string of item doing the replacing.
   */
//  public function processUpdatedDataset(string $old, $new = "{}") {
//    $references_removed = $this->referencesRemoved($old, $new);
//
//    $orphan_reference_queue = $this->queueService->get('orphan_property_processor');
//    foreach ($references_removed as $reference_removed) {
//       @Todo: Only add to the queue when uuid doesn't already exists in it.
//      $orphan_reference_queue->createItem($reference_removed);
//    }
//  }

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
  protected function referencesRemoved($old, $new = "{}") {
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
