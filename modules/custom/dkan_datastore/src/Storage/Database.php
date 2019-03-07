<?php

namespace Drupal\dkan_datastore\Storage;

use Dkan\Datastore\Storage\Database\Query\Insert;
use Dkan\Datastore\Storage\IDatabase;

class Database implements IDatabase
{
  private $connection;

  public function __construct(\Drupal\Core\Database\Connection $connection) {
    $this->connection = $connection;
  }

  public function tableExist($table_name) {
    $exists = $this->connection->schema()->tableExists($table_name);
    return $exists;
  }

  public function tableCreate($table_name, $schema) {
    db_create_table($table_name, $schema);
  }

  public function tableDrop($table_name) {
    $this->connection->schema()->dropTable($table_name);
  }

  public function count($table_name) {
    if ($this->tableExist($table_name)) {
      $query = db_select($table_name);
      return $query->countQuery()->execute()->fetchField();
    }
    throw new \Exception("Table {$table_name} does not exist.");
  }

  public function insert(Insert $query) {
    if ($this->tableExist($query->tableName)) {
      $q = db_insert($query->tableName);
      $q->fields($query->fields);
      foreach ($query->values as $values) {
        $q->values($values);
      }
      $q->execute();
    }
  }

  /**
   * Performs a single query against the datastore.
   */
  public function execute_api_query($params) {

    $data_select = NULL;

    try {
      $resource_ids = (array) $params['resource_ids'];

      if (empty($resource_ids)) {
        throw new \Exception("The resource_id is a required parameter.");
      }
      $keys = array_keys($resource_ids);
      $values = array_values($resource_ids);
      $alias = array_shift($keys);
      $resource_id = array_shift($values);

      $alias = is_string($alias) ? $alias : DKAN_DATASTORE_API_DEFAULT_TABLE_ALIAS;
      $table = $this->get_api_tablename($resource_id);
      $data_select = db_select($table, $alias);

      $this->set_api_limit($data_select, $params['offset'], $params['limit']);
      $this->set_api_sort($data_select, $params['sort'], $alias);
      $this->set_api_where($data_select, $params['filters']);

      $count = $this->get_api_count($data_select);
      $results = $data_select->execute();

      return $this->get_query_output($data_select, $results, $table, $params['fields'], $resource_ids, $count, $params['limit']);
    }
    catch (Exception $e) {
      /*$info = "";
      if ($data_select) {
        $info = dkan_datastore_api_debug($data_select);
      }
      return array('sql' => $info, 'error' => array('message' => 'Caught exception: ' . $e->getMessage()));*/
    }
  }

  /**
   * Returns table name or exception.
   */
  protected function get_api_tablename($resource_id) {
    $resource = Resource::createFromDrupalNodeUuid($resource_id);
    /* @var $manager \Dkan\Datastore\Manager\ManagerInterface */
    $manager = (new Factory($resource))->get();
    $table = $manager->getTableName();
    return $table;
  }

  /**
   * Sort provided data array.
   *
   * @param mixed $data_select
   *   SelectQuery object to sort.
   * @param mixed $sort
   *   Sort criteria.
   * @param string $alias
   *   Drupal Database alias.
   */
  protected function set_api_sort(&$data_select, $sort, $alias) {
    if (!is_array($sort)) {
      $sort = explode(' ', $sort);
      $columns = empty($sort) ? $sort : explode(',', $sort[0]);
    }
    else {
      $columns = $sort;
    }
    $vs = array_values($sort);
    $ks = array_keys($sort);
    $key = end($vs);
    if (!in_array($key, ['desc', 'asc'])) {
      $columns = $key;
      $alias = end($ks);
    }
    if (!empty($columns)) {
      foreach ($columns as $field => $order) {
        $data_select->orderBy($alias . '.' . $field, $order);
      }
    }
  }

  /**
   * Adds conditions, ie WHERE clause, to query.
   */
  protected function set_api_where(&$data_select, $filters) {
    if ($filters) {

      // Fields without table prefix.
      // e.g.: "filters": [{ "WL_1_County": [1] }].
      if (!is_assoc($filters)) {
        foreach ($filters as $field => $value) {
          $field = str_replace(' ', '_', $field);
          $value = is_array($value) ? $value : explode(',', $value);
          $data_select->condition($field, $value, dkan_datastore_api_clause_condition($value));
        }
        // Fields with table prefix.
        // e.g. {"counties": {"msa": "N"}}.
      }
      elseif (is_assoc($filters)) {
        foreach ($filters as $key => $value) {

          // Fields with table prefix again.
          // {"counties": {"msa": "N"}, "msab": ... }.
          if (is_array($value) && is_assoc($value)) {
            foreach ($value as $field => $filterValue) {
              $table = $key;
              $field = str_replace(' ', '_', $field);
              $filterValue = is_array($filterValue) ? $filterValue : explode(',', $filterValue);
              $data_select->condition($table . '.' . $field, $filterValue, dkan_datastore_api_clause_condition($value));
            }

            // Fields without table prefix again.
            // {"counties": ..., "msab": [1,2] }.
          }
          else {
            $key = str_replace(' ', '_', $key);
            $value = is_array($value) ? $value : explode(',', $value);
            $data_select->condition($key, $value, dkan_datastore_api_clause_condition($value));
          }
        }
      }
    }
  }

  /**
   * Restrict the result to a range.
   */
  protected function set_api_limit(&$data_select, $offset, $limit) {
    $default_limit = variable_get('dkan_datastore_default_page_size', 100);
    if ((!user_access('perform unlimited index queries') && $limit > $default_limit) || !$limit) {
      $limit = $default_limit;
    }
    $data_select->range($offset, $limit);
  }

  /**
   * Retrieves count given fully query without paging limit.
   */
  protected function get_api_count($data_select) {
    $query = clone($data_select);
    $count = $query->range()->countQuery()->execute()->fetchField();
    return $count;
  }

  /**
   * Builds index link with results of the query.
   */
  protected function get_query_output($data_select, $results, $table, $fields, $resource_ids, $count, $limit) {
    $fields = normalize_fields($fields, $resource_ids);

    // Put together array of matching items to return.
    $items = array();
    foreach ($results as $result) {
      $items[] = $result;
    }

    $help = array('help' => dkan_datastore_api_resource_help());
    $success = count($items) ? array('success' => TRUE) : array('success' => FALSE);
    $schema_fields = schema_fields($resource_ids);

    // Prepare rows.
    $items = array_map(function ($item) use ($schema_fields) {
      $new_item = new stdClass();
      foreach ($item as $name => $data) {
        if (!dkan_datastore_api_field_excluded($name)) {
          if (!empty($schema_fields[$name])) {
            $new_name = $schema_fields[$name]['label'];
            if ($new_name) {
              $new_item->$new_name = $data;
            }
            else {
              $new_item->$name = $data;
            }
          }
          else {
            $new_item->$name = $data;
          }
        }
      }
      return $new_item;
    }, $items);

    $return = new stdClass();

    // Prepare schema.
    foreach (prepare_fields($fields) as $table_fields) {
      foreach ($table_fields as $table_field) {
        $field = $schema_fields[$table_field];
        $name = $field['label'];
        $type = preg_replace('/date|timestamp/', 'datetime', $field['type']);
        $return->fields[] = array('id' => $name, 'type' => $type);
      }
    }

    // Prepare output.
    $return->resource_id = $resource_ids;
    $return->limit = (int) $limit;
    $return->total = (int) $count;
    $return->records = $items;
    return $help + $success + array('result' => $return);
  }

}
