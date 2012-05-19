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
  public function load($key) {
    $result = $this->loadExportables('keys', array($key));
    if (isset($result[$name])) {
      return $result[$name];
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
    $result = $this->loadExportables('all');
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
  public function loadObjects($type = 'all', $args = array()) {
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
    $query = db_select($this->info['table'], 't__0')->fields('t__0');

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
    foreach ($result as $data) {
      $object = new $this->info['exportable class']($data);

      // @todo -- these need to be fixed
//      $object->{$this->info['export type string']} = t('Normal');
      $object->setIsInDatabase(TRUE);
      // Determine if default object is enabled or disabled.
      if (isset($status[$object->{$this->info['key']}])) {
        $object->disabled = $status[$object->{$this->info['key']}];
      }

      $this->cache[$object->{$this->info['key']}] = $object;
      if ($type == 'conditions') {
        $return[$object->{$this->info['key']}] = $object;
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
          if (!in_array($object->{$this->info['key']}, $args)) {
            continue;
          }
        }

        // Determine if default object is enabled or disabled.
        if (isset($status[$object->{$this->info['key']}])) {
          $object->disabled = $status[$object->{$this->info['key']}];
        }

        // If we found a default but it's in the dtabase, mark it so.
        if (!empty($this->cache[$object->{$this->info['key']}])) {
//          $this->cache[$object->{$this->info['key']}]->{$this->info['export type string']} = t('Overridden');
          $this->cache[$object->{$this->info['key']}]->setIsInCode(TRUE);
          $this->cache[$object->{$this->info['key']}]->setExportModule(isset($object->export_module) ? $object->export_module : NULL);
          if ($type == 'conditions') {
            $return[$object->{$this->info['key']}] = $this->cache[$object->{$this->info['key']}];
          }
        }
        else {
//          $object->{$this->info['export type string']} = t('Default');
          $object->export_type = EXPORT_IN_CODE;
          $this->cache[$object->{$this->info['key']}]->setIsInCode(TRUE);

          $this->cache[$object->{$this->info['key']}] = $object;
          if ($type == 'conditions') {
            $return[$object->{$this->info['key']}] = $object;
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

  /**
   * @todo.
   */
  public function create() {
  }

  /**
   * @todo.
   */
  public function save($this->infoable) {
  }

  /**
   * @param $keys Array
   */
  public function delete($keys) {
  }

  /**
   * @todo.
   */
  public function setStatus($this->infoable, $status) {
    $status = variable_get($this->info['status'], array());
    $key = $this->info['key'];

    // Compare
    if (!$new_status && $this->infoable->isInDatabase()) {
      unset($status[$this->infoable->{$key}]);
    }
    else {
      $status[$this->infoable->{$key}] = $new_status;
    }

    variable_set($this->info['status'], $status);
  }

}
