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
  public function __construct($type);

  /**
   * @todo.
   */
  public function getType();

  /**
   * @todo.
   */
  public function getInfo();

  /**
   * @todo.
   */
  public function getSchema();

  /**
   * @todo.
   */
  public function load($key);

  /**
   * @todo.
   */
  public function loadMultiple(array $keys);

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
  public function create(array $data = array());

  /**
   * @todo.
   */
  public function save($exportable);

  /**
   * @param $keys Array
   */
  public function delete($keys);

  /**
   * @todo.
   */
  public function enable($exportable);

  /**
   * @todo.
   */
  public function disable($exportable);

  /**
   * @todo.
   */
  public function setStatus($exportable, $status);

  /**
   * @todo.
   */
  public function export($exportable, $indent = '');

  /**
   * @todo.
   */
  public function createDuplicate($exportable);
}
