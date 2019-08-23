<?php

namespace Drupal\dkan_datastore\Storage;

use Procrastinator\Job\Job;
use Dkan\Datastore\Importer;
use Drupal\Core\Database\Connection;

/**
 * Retrieve a serialized job (datastore importer or harvest) from the database.
 *
 * @todo should probably be a service in its own module.
 */
class JobStore {

  private $connection;
  private $jobClass;

  public function __construct(Connection $connection, string $jobClass) {
    $this->connection = $connection;
    if (!$this->validateJobClass($jobClass)) {
      $this->jobClass = $jobClass;
    }
    else {
      throw new \Exception("Invalid job class provided: $jobClass");
    }
  }

  public function retrieve(string $uuid) {
    $tableName = self::getTableName($this->jobClass);
    if (!$this->tableExists($tableName)) {
      $this->createTable($tableName);
    }

    $result = $this->connection->select($tableName, 't')
      ->fields('t', ['job_data'])
      ->condition('ref_uuid', $uuid)
      ->execute()
      ->fetch();
    if (!empty($result)) {
      $job = $this->jobClass::hydrate($result->job_data);
    }
    if (($job instanceof $this->jobClass)) {
      return $job;
    }
  }

  public function store(Job $job, string $uuid) {
    // Validate incoming JSON by trying to hydrate it.
    if (!($job instanceof $this->jobClass)) {
      throw new \Exception("Invalid object passed to jobStore.");
    }
    $tableName = self::getTableName($this->jobClass);

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
    $tableName = self::getTableName($this->jobClass);
    $this->connection->delete($tableName)
      ->condition('ref_uuid', $uuid)
      ->execute();
  }

  public static function getTableName($jobClass) {
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
