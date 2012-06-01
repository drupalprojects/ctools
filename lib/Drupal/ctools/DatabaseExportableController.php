<?php

/**
 * @file
 * Definition of Drupal\ctools\DatabaseExportableController.
 */

namespace Drupal\ctools;

/**
 * @todo.
 */
class DatabaseExportableController extends ExportableControllerBase {
  protected $cache = array();
  protected $cacheAll = FALSE;
  // @todo something has to load this.
  protected $schema = NULL;

  /**
   * @todo.
   */
  public function __construct($type) {
    parent::__construct($type);
    $this->type = $type;

    // @todo CTools had some code to work around schema caching issues
    // that we may need to replicate. These were particularly difficult
    // issues during the module enable process.
    $this->schema = drupal_get_schema($this->info['schema']);
  }

  /**
   * @todo.
   */
  public function getSchema() {
    return $this->schema;
  }

  /**
   * @todo.
   */
  public function load($key) {
    $result = $this->loadExportables('keys', array($key));
    if (isset($result[$key])) {
      return $result[$key];
    }
  }

  /**
   * @todo.
   */
  public function loadMultiple(array $keys) {
    $result = $this->loadExportables('keys', $keys);

    // Ensure no empty results are returned.
    return array_filter($results);
  }

  /**
   * @todo.
   */
  public function loadAll(/* $pageSize, $pageNumber */) {
    return $this->loadExportables('all');
  }

  /**
   * Load some number of exportable objects.
   *
   * This function will cache the objects, load subsidiary objects if necessary,
   * check default objects in code and properly set them up. It will cache
   * the results so that multiple calls to load the same objects
   * will not cause problems.
   *
   * It attempts to reduce, as much as possible, the number of queries
   * involved.
   *
   * @param $type
   *   A string to notify the loader what the argument is
   *   - all: load all items. This is the default. $args is unused.
   *   - keys: $args will be an array of specific named objects to load.
   *   - conditions: $args will be a keyed array of conditions. The conditions
   *       must be in the schema for this table or errors will result.
   * @param $args
   *   An array of arguments whose actual use is defined by the $type argument.
   */
  public function loadExportables($type = 'all', $args = array()) {
    // If fetching all and cached all, we've done so and we are finished.
    if ($type == 'all' && !empty($this->cacheAll)) {
      return $this->cache;
    }

    $return = array();

    // Don't load anything we've already cached.
    if ($type == 'keys' && !empty($args)) {
      foreach ($args as $id => $name) {
        if (isset($this->cache[$name])) {
          $return[$name] = $this->cache[$name];
          unset($args[$id]);
        }
      }

      // If nothing left to load, return the result.
      if (empty($args)) {
        return $return;
      }
    }

    // Build the query
    $query = db_select($this->info['schema'], 't__0')->fields('t__0');

    /** Hate this code
     * maybe we can just get rid of it

    $alias_count = 1;
    if (!empty($schema['join'])) {
      foreach ($schema['join'] as $join_key => $join) {
        if ($join_schema = drupal_get_schema($join['table'])) {
          $query->join($join['table'], 't__' . $alias_count, 't__0.' . $join['left_key'] . ' = ' . 't__' . $alias_count . '.' . $join['right_key']);
          $query->fields('t__' . $alias_count);
          $alias_count++;

          // Allow joining tables to alter the query through a callback.
          if (isset($join['callback']) && function_exists($join['callback'])) {
            $join['callback']($query, $schema, $join_schema);
          }
        }
      }
    }

    */

    $conditions = array();
    $query_args = array();

    // If they passed in names, add them to the query.
    if ($type == 'keys') {
      $query->condition($this->info['key'], $args, 'IN');
    }
    else if ($type == 'conditions') {
      foreach ($args as $key => $value) {
        if (isset($this->schema['fields'][$key])) {
          $query->condition($key, $value);
        }
      }
    }

    $result = $query->execute();

    $status = variable_get($this->info['status'], array());
    // Unpack the results of the query onto objects and cache them.
    while ($data = $result->fetchAssoc()) {
      $object = new $this->info['exportable class']($data, $this->type);

      // @todo -- these need to be fixed
//      $object->{$this->info['export type string']} = t('Normal');
      $object->setIsInDatabase(TRUE);
      // Determine if default object is enabled or disabled.
      if (isset($status[$object->id()])) {
        $object->disabled = $status[$object->id()];
      }
      else {
        $object->disabled = FALSE;
      }

      // Set the API version.
      $object->api_version = $this->info['api']['current_version'];
      // Set the export module name.
      $object->setExportModule($this->schema['module']);

      $this->cache[$object->id()] = $object;
      if ($type == 'conditions') {
        $return[$object->id()] = $object;
      }
    }

    // @todo -- if we do it this way we really shouldn't put them in
    // $this->cache until after they've been altered.
    if (method_exists($this, 'loadAlter')) {
      $this->loadAlter($this->cache);
    }

    $defaults = $this->getDefaultExportables($args);

/* -- left in to show that there's some oddness here that exists due
 * to the caching of default objects.

    if ($type == 'keys' && !empty($args) && !empty($this->info['cache defaults'])) {
      $defaults = _ctools_export_get_some_defaults($table, $this->info, $args);
    }
    else {
      $defaults = _ctools_export_get_defaults($table, $this->info);
    }
*/
    if ($defaults) {
      foreach ($defaults as $object) {
        if ($type == 'conditions') {
          // if this does not match all of our conditions, skip it.
          foreach ($args as $key => $value) {
            if (!isset($object->$key)) {
              continue 2;
            }
            if (is_array($value)) {
              if (!in_array($object->$key, $value)) {
                continue 2;
              }
            }
            else if ($object->$key != $value) {
              continue 2;
            }
          }
        }
        else if ($type == 'keys') {
          if (!in_array($object->id(), $args)) {
            continue;
          }
        }

        // Determine if default object is enabled or disabled.
        if (isset($status[$object->id()])) {
          $object->disabled = $status[$object->id()];
        }

        // If we found a default but it's in the dtabase, mark it so.
        if (!empty($this->cache[$object->id()])) {
//          $this->cache[$object->id()]->{$this->info['export type string']} = t('Overridden');
          $this->cache[$object->id()]->setIsInCode(TRUE);
          $this->cache[$object->id()]->setExportModule($object->getExportModule());
          if ($type == 'conditions') {
            $return[$object->id()] = $this->cache[$object->id()];
          }
        }
        else {
//          $object->{$this->info['export type string']} = t('Default');
//          $object->export_type = EXPORT_IN_CODE;
          $object->setIsInCode(TRUE);

          $this->cache[$object->id()] = $object;
          if ($type == 'conditions') {
            $return[$object->id()] = $object;
          }
        }
      }
    }

    // If fetching all, we've done so and we are finished.
    if ($type == 'all') {
      $this->cacheAll = TRUE;
      return $this->cache;
    }

    if ($type == 'keys') {
      foreach ($args as $name) {
        if (isset($this->cache[$name])) {
          $return[$name] = $this->cache[$name];
        }
      }
    }

    // For conditions,
    return $return;
  }

  function getDefaultExportables($args = NULL) {
    if (isset($this->cachedDefaults)) {
      return $this->cachedDefaults;
    }

    if ($this->info['default hook']) {
      if (!empty($this->info['api'])) {
        ctools_include('plugins');
        $info = ctools_plugin_api_include($this->info['api']['owner'], $this->info['api']['api'],
          $this->info['api']['minimum_version'], $this->info['api']['current_version']);
        $modules = array_keys($info);
      }
      else {
        $modules = module_implements($this->info['default hook']);
      }

      foreach ($modules as $module) {
        $function = $module . '_' . $this->info['default hook'];
        if (function_exists($function)) {
          foreach ((array) $function($this->info) as $name => $data) {
            $object = new $this->info['exportable class']($data, $this->type);

            // Record the module that provides this exportable.
            $object->setExportModule($module);

            if (empty($this->info['api'])) {
              $this->cachedDefaults[$name] = $object;
            }
            else {
              // If version checking is enabled, ensure that the object can be used.
              print($this->info['api']);
              if (isset($object->api_version) &&
                version_compare($object->api_version, $this->info['api']['minimum_version']) >= 0 &&
                version_compare($object->api_version, $this->info['api']['current_version']) <= 0) {
                $this->cachedDefaults[$name] = $object;
              }
            }
          }
        }
      }

      drupal_alter($this->info['default hook'], $this->cachedDefaults);
    }
    return $this->cachedDefaults;
  }

  /**
   * @todo.
   */
  public function create(array $data = array()) {
    // Populate default values.
    foreach ($this->schema['fields'] as $field => $info) {
      // Get a default if nothing exists.
      if (!isset($data[$field])) {
        $data[$field] = !empty($info['default']) ? $info['default'] : NULL;
      }
    }

    return new $this->info['exportable class']($data, $this->type);
  }

  /**
   * @todo.
   */
  public function save($exportable) {
    // Objects should have a serial primary key. If not, simply fail to write.
    if (empty($this->schema['primary key'])) {
      return FALSE;
    }

    if ($exportable->isInDatabase()) {
      // Existing record.
      $update = $this->schema['primary key'];
    }
    else {
      // New record.
      $update = array();
      $exportable->setIsInDatabase(TRUE);
    }

    return drupal_write_record($this->info['schema'], $exportable, $update);
  }

  /**
   * @param $keys Array
   */
  public function delete($keys) {
    db_delete($this->info['schema'])
      ->condition($this->info['key'], $keys)
      ->execute();
  }

  /**
   * @todo.
   */
  public function setStatus($exportable, $status) {
    $status = variable_get($this->info['status'], array());
    $key = $this->info['key'];

    // Compare
    if (!$new_status && $exportable->isInDatabase()) {
      unset($status[$exportable->{$key}]);
    }
    else {
      $status[$exportable->{$key}] = $new_status;
    }

    variable_set($this->info['status'], $status);
  }

  /**
   * @todo.
   */
  public function unpack($exportable, $data) {
    // Go through our schema and build correlations.
    foreach ($data as $field => $value) {
      if (isset($this->schema['fields'][$field])) {
        $exportable->$field = !empty($this->schema['fields'][$field]['serialize']) ? unserialize($data[$field]) : $data[$field];
      }
      else {
        $exportable->$field = $value;
      }
    }

    if (isset($this->schema['join'])) {
      foreach ($this->schema['join'] as $join_key => $join) {
        if (!empty($join['load'])) {
          // Might just want drupal_get_schema here later on?
          $join_schema = ctools_export_get_schema($join['table']);
          foreach ($join['load'] as $field) {
            $exportable->$field = !empty($join_schema['fields'][$field]['serialize']) ? unserialize($data[$field]) : $data[$field];
          }
        }
      }
    }

  }

}
