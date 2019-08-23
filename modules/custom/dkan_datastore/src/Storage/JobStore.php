<?php

namespace Drupal\dkan_datastore\Storage;

use Procrastinator\Job\Job;
use Drupal\Core\Database\Connection;
use Contracts\RemoverInterface;
use Contracts\RetrieverInterface;
use Contracts\StorerInterface;

/**
 * Retrieve a serialized job (datastore importer or harvest) from the database.
 *
 * @todo should probably be a service in its own module.
 */
class JobStore implements RemoverInterface, RetrieverInterface, StorerInterface {

  private $connection;
  private $jobClass;

  public function __construct(Connection $connection, $jobClass) {
    $this->connection = $connection;
    if (!$this->validateJobClass($jobClass)) {
      $this->jobClass = $jobClass;
    }
    else {
      throw new \Exception("Invalid job class provided: $jobClass");
    }
  }

  public function retrieve(string $uuid) {
    $tableName = $this->getTableName($this->jobClass);
    if (!$this->tableExists($tableName)) {
      $this->createTable($tableName);
    }

    $result = $this->connection->select($tableName, 't')
      ->fields('t', ['job_data'])
      ->condition('ref_uuid', $uuid)
      ->execute()
      ->fetch();
    if (!empty($result)) {
      $job = $jobClass::hydrate($result->job_data);
    }
    if (isset($job) && ($job instanceof $this->jobClass)) {
      return $job;
    }
  }

  public function store(string $jobJson, string $uuid) {
    // Validate incoming JSON by trying to hydrate it.
    $job = $this->jobClass::hydrate($jobJson);
    if (!($job instanceof $this->jobClass)) {
      throw new \Exception("Invalid job data passed to jobStore: $jobJson");
    }
    $tableName = $this->getTableName($this->jobClass);

    if (!$this->tableExists($tableName)) {
      $this->createTable($tableName);
    }
    $data = json_encode($job);
    $q = $this->connection->insert($tableName);
    $q->fields(['ref_uuid', 'job_data'])
      ->values([$uuid, $data])
      ->execute();
  }

  public function remove(string $uuid) {
    $tableName = $this->getTableName($this->jobClass);
    $this->connection->delete($tableName)
      ->condition('ref_uuid', $uuid)
      ->execute();
  }

  private function getTableName($jobClass) {
    $safeClassName = strtolower(preg_replace('/\\\\/', '_', $jobClass));
    return 'jobstore_' . $safeClassName;
  }

  private function createTable(string $tableName) {
    $schema = [
      'fields' => [
        'jid' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
        'ref_uuid' => ['type' => 'varchar', 'length' => 128],
        'job_data' => ['type' => 'text', 'length' => 65535],
      ],
      'indexes' => [
        'jid' => ['jid'],
      ],
      'foriegn_keys' => [
        'ref_uuid' => ['table' => 'node', 'columns' => ['uuid' => 'uuid']],
      ],
      'primary_key' => ['jid'],
    ];
    $this->connection->schema()->createTable($tableName, $schema);
  }

  /**
   * Check for existence of a table name.
   */
  private function tableExists($tableName) {
    $exists = $this->connection->schema()->tableExists($tableName);
    return $exists;
  }

  private function validateJobClass(string $jobClass): bool {
    if (is_subclass_of($jobClass, Job::class)) {
      return TRUE;
    }
    return FALSE;
  }

}
