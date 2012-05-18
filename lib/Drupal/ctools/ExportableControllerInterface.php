<?php

/**
 * @file
 * Definition of Drupal\ctools\ExportableControllerInterface.
 */

namespace Drupal\ctools;

/**
 * @todo.
 */
interface ExportableControllerInterface {

  /**
   * @todo.
   */
  public function __construct($data);

  /**
   * @todo.
   */
  public function load($key);

  /**
   * @todo.
   */
  public function loadMultiple($key, $conditions);

  /**
   * @todo.
   */
  public function loadAll(/* $pageSize, $pageNumber */);

  /**
   * @todo.
   */
  public function import($code);

  /**
   * @todo.
   */
  public function create();

}
