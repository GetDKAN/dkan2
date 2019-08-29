<?php

namespace Drupal\dkan_datastore\Storage;

use Dkan\Datastore\Storage\StorageInterface;
use Drupal\Core\Database\Connection;
use Dkan\Datastore\Resource;

/**
 * Database storage object.
 *
 * @see Dkan\Datastore\Storage\StorageInterface
 */
class DatabaseTable implements StorageInterface {

  use \Dkan\Datastore\Storage\Database\SqlStorageTrait;

  private $connection;

  /**
   * Datastore resource object.
   *
   * @var \Dkan\Datastore\Resource
   */
  private $resource;
  private $schema;

  /**
   * Constructor method.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Drupal database connection object.
   */
  public function __construct(Connection $connection, Resource $resource) {
    $this->connection = $connection;
    $this->resource = $resource;
    if (!$this->schema && $this->tableExist($this->getTableName())) {
      $this->setSchemaFromTable();
    }
  }

  /**
   * Method wrapper for retrieveAll..
   *
   * @todo Implement.
   */
  public function retrieveAll(): array {
  }

  /**
   * Retrieve method.
   *
   * @todo Implement.
   */
  public function retrieve(string $id): ?string {
  }

  /**
   * Store data.
   */
  public function store(string $data, string $id = NULL): string {
    if (!$this->schema) {
      throw new \Exception("Schema is required.");
    }

    if (!$this->tableExist($this->getTableName())) {
      $this->tableCreate($this->getTableName(), $this->schema);
    }

    $data = json_decode($data);

    $q = db_insert($this->getTableName());
    $q->fields(array_keys($this->schema['fields']));
    foreach ($data as $values) {
      $q->values($values);
    }
    $q->execute();

    return "SUCCESS";
  }

  /**
   * Remove() method.
   *
   * @todo: Implement.
   */
  public function remove(string $id) {}

  /**
   * Count rows in table.
   */
  public function count(): int {
    if ($this->tableExist($this->getTableName())) {
      $query = $this->connection->select($this->getTableName());
      return $query->countQuery()->execute()->fetchField();
    }
    throw new \Exception("Table {$this->getTableName()} does not exist.");
  }

  public function getSummary() {
    $columns = array_keys($this->schema['fields']);
    $numOfColumns = count($columns);
    $numOfRows = $this->count();
    return new TableSummary($numOfColumns, $columns, $numOfRows);
  }

  /**
   * Run a query on the database table.
   *
   * @param \Drupal\dkan_datastore\Storage\Query $query
   *   Query obejct.
   */
  public function query(Query $query): array {
    $db_query = $this->connection->select($this->getTableName(), 't');
    $db_query->fields('t', $query->properties);

    foreach ($query->conditions as $property => $value) {
      $db_query->condition($property, $value, "LIKE");
    }

    foreach ($query->sort['ASC'] as $property) {
      $db_query->orderBy($property);
    }

    foreach ($query->sort['DESC'] as $property) {
      $db_query->orderBy($property, 'DESC');
    }

    if ($query->limit) {
      if ($query->offset) {
        $db_query->range($query->offset, $query->limit);
      }
      else {
        $db_query->range(1, $query->limit);
      }
    }
    elseif ($query->offset) {
      $db_query->range($query->limit);
    }

    if ($query->count) {
      $db_query = $db_query->countQuery();
    }

    $result = $db_query->execute()->fetchAll();

    return $result;
  }

  public function destroy() {
    if ($this->tableExist($this->getTableName())) {
      $this->tableDrop($this->getTableName());
    }
  }

  /**
   * Get the full name of datastore db table.
   *
   * @return string
   *   Table name.
   */
  private function getTableName() {
    if ($this->resource) {
      return "dkan_datastore_{$this->resource->getId()}";
    }
    else {
      return "";
    }
  }

  /**
   * Get table schema.
   */
  private function getTableSchema($fields) {
    $schema = [];
    $header = $fields;
    foreach ($header as $field) {
      $schema['fields'][$field] = [
        'type' => "text",
      ];
    }
    return $schema;
  }

  /**
   * Check for existence of a table name.
   */
  private function tableExist($table_name) {
    $exists = $this->connection->schema()->tableExists($table_name);
    return $exists;
  }

  /**
   * Create a table given a name and schema.
   */
  private function tableCreate($table_name, $schema) {
    $this->connection->schema()->createTable($table_name, $schema);
  }

  /**
   * Drop a table from the db.
   */
  private function tableDrop($table_name) {
    $this->connection->schema()->dropTable($table_name);
  }

  /**
   * Set the schema using the existing database table..
   */
  private function setSchemaFromTable() {
    $fields_info = $this->connection->query("DESCRIBE `{$this->getTableName()}`")->fetchAll();
    if (!empty($fields_info)) {
      $fields = $this->getFieldsFromFieldsInfo($fields_info);
      $this->setSchema($this->getTableSchema($fields));
    }
  }

  /**
   * Get field names from results of a DESCRIBE query.
   *
   * @param array $fields_info
   *   Array containing thre results of a DESCRIBE query sent to db connection.
   */
  private function getFieldsFromFieldsInfo(array $fields_info) {
    $fields = [];
    foreach ($fields_info as $info) {
      $fields[] = $info->Field;
    }
    return $fields;
  }

}
