<?php

namespace Drupal\dkan_datastore\Storage;

use Drupal\dkan_common\Storage\AbstractDatabaseTable;
use Procrastinator\Job\Job;
use Drupal\Core\Database\Connection;

/**
 * Retrieve a serialized job (datastore importer or harvest) from the database.
 */
class JobStore extends AbstractDatabaseTable {

  private $jobClass;

  /**
   * Constructor.
   */
  public function __construct(string $jobClass, Connection $connection) {
    if (!$this->validateJobClass($jobClass)) {
      throw new \Exception("Invalid jobType provided: $jobClass");
    }
    $this->jobClass = $jobClass;
    $this->setOurSchema();
    parent::__construct($connection);
  }

  /**
   * Get.
   */
  public function retrieve(string $id) {
    $result = parent::retrieve($id);
    return $result->job_data;
  }

  /**
   * Protected.
   */
  protected function getTableName() {
    $safeClassName = strtolower(preg_replace('/\\\\/', '_', $this->jobClass));
    return 'jobstore_' . $safeClassName;
  }

  /**
   * Private.
   */
  private function setOurSchema() {
    $schema = [
      'fields' => [
        'ref_uuid' => ['type' => 'varchar', 'length' => 128, 'not null' => TRUE],
        'job_data' => ['type' => 'text', 'length' => 65535],
      ],
      'indexes' => [
        'ref_uuid' => ['ref_uuid'],
      ],
      'foreign keys' => [
        'ref_uuid' => ['table' => 'node', 'columns' => ['uuid' => 'uuid']],
      ],
      'primary key' => ['ref_uuid'],
    ];

    $this->setSchema($schema);
  }

  /**
   * Private.
   */
  private function validateJobClass(string $jobClass): bool {
    if (is_subclass_of($jobClass, Job::class)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Inherited.
   *
   * @inheritDoc
   */
  protected function prepareData(string $data, string $id = NULL): array {
    return ['ref_uuid' => $id, 'job_data' => $data];
  }

  /**
   * Inherited.
   *
   * @inheritDoc
   */
  protected function primaryKey() {
    return 'ref_uuid';
  }

}
