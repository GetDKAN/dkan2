<?php

namespace Drupal\dkan_datastore\Storage;

use Procrastinator\Job\Job;
use Drupal\Core\Database\Connection;

/**
 * Retrieve a serialized job (datastore importer or harvest) from the database.
 *
 * @todo should probably be a service in its own module.
 */
class JobStore {

  private $connection;

  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  public function get(string $uuid, string $jobClass) {
    if (!$this->validateJobClass($jobClass)) {
      throw new \Exception("Invalid jobType provided: $jobClass");
    }

    $tableName = $this->getTableName($jobClass);
    if (!$this->tableExists($tableName)) {
      throw new \Exception("Table $tableName does not exist.");
    }

    $result = $this->connection->select($tableName, 't')
      ->fields('t', ['data'])
      ->condition('ref_uuid', $uuid)
      ->execute()
      ->fetch();
    if (!empty($result)) {
      $job = $jobClass::hydrate($result);
    }
    if (isset($job) && ($job instanceof $jobClass)) {
      return $job;
    }
  }

  public function store(Job $job, string $uuid) {
    $jobClass = get_class($job);
    $tableName = $this->getTableName($jobClass);

    if (!$this->tableExists($tableName)) {
      $this->createTable($tableName);
    }
    $data = $job->jsonSerialize();
    $q = $this->connection->insert($tableName)
      ->fields(['ref_uuid', 'job_data'])
      ->values([$uuid, $data])
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
        'job_data' => ['type' => 'text'],
      ],
      'indexes' => [
        'jid' => ['jid'],
      ],
      'foriegn_keys' => [
        'ref_uuid' => ['table' => 'node', 'columns' => ['uuid' => 'uuid']],
      ],
      'primary_key' => ['nid'],
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
