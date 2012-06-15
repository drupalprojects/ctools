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
   * Returns whether the exportable is enabled.
   *
   * @return bool
   *   TRUE if the exportable is enabled and FALSE otherwise.
   */
  public function isEnabled();

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

  /**
   * Returns the exportable type, as used in ctools_exportable_get_info().
   */
  public function getExportableType();

  /**
   * @todo.
   */
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

  /**
   * @todo.
   */
  public function id();

  /**
   * Fetches the title of this exportable.
   *
   * @return string
   *   The title of this exportable.
   */
  public function title();

}
