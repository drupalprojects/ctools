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
  public function __construct($data);

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
  public function isInCode();

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
