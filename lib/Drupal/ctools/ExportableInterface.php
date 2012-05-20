<?php

/**
 * @file
 * Definition of Drupal\ctools\ExportableInterface.
 */

namespace Drupal\ctools;

/**
 * @todo.
 */
interface ExportableInterface {

  /**
   * @todo.
   */
  public function __construct($data, $exportableType = NULL);

  /**
   * @todo.
   */
  public function save();

  /**
   * @todo.
   */
  public function delete();

  /**
   * @todo.
   */
  public function enable();

  /**
   * @todo.
   */
  public function disable();

  /**
   * @todo.
   */
  public function isInDatabase();

  /**
   * @todo.
   */
  public function setIsInDatabase($status);

  /**
   * @todo.
   */
  public function isInCode();

  /**
   * @todo.
   */
  public function setIsInCode($status);

  public function getExportModule();

  /**
   * @todo.
   */
  public function setExportModule($module);

  /**
   * @todo.
   */
  public function isOverridden();

  /**
   * @todo.
   */
  public function export($indent = '');

  /**
   * @todo.
   */
  public function createDuplicate();

}
