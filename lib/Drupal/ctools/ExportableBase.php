<?php

/**
 * @file
 * Definition of Drupal\ctools\ExportableBase.
 */

namespace Drupal\ctools;

/**
 * @todo.
 */
abstract class ExportableBase implements ExportableInterface {

  /**
   * @todo.
   *
   * @var string
   */
  protected $exportableType;

  /**
   * @todo.
   *
   * @var bool
   */
  protected $exportableInDatabase;

  /**
   * @todo.
   *
   * @var bool
   */
  protected $exportableInCode;

  /**
   * @todo.
   */
  public function __construct($data) {
  }

  /**
   * @todo.
   */
  public function save() {
    ctools_exportable_get_controller($this->exportableType)->save($this);
  }

  /**
   * @todo.
   */
  public function delete() {
    ctools_exportable_get_controller($this->exportableType)->delete(array($this->id()));
  }

  /**
   * @todo.
   */
  public function enable() {
    ctools_exportable_get_controller($this->exportableType)->enable($this);
  }

  /**
   * @todo.
   */
  public function disable() {
    ctools_exportable_get_controller($this->exportableType)->disable($this);
  }

  /**
   * @todo.
   */
  public function isInDatabase() {
    return $this->exportableInDatabase;
  }

  /**
   * @todo.
   */
  public function isInCode() {
    return $this->exportableInCode;
  }

  /**
   * @todo.
   */
  public function isOverridden() {
    return $this->exportableInCode && $this->exportableInDatabase;
  }

  /**
   * @todo.
   */
  public function export($indent = '') {
    ctools_exportable_get_controller($this->exportableType)->export($this, $indent);
  }

  /**
   * @todo.
   */
  public function createDuplicate() {
    ctools_exportable_get_controller($this->exportableType)->createDuplicate($this);
  }

}
