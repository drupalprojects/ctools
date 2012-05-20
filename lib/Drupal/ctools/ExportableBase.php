<?php

/**
 * @file
 * Definition of Drupal\ctools\ExportableBase.
 */

namespace Drupal\ctools;

/**
 * @todo.
 */
class ExportableBase implements ExportableInterface {

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
   *
   * @var bool
   */
  protected $exportableModule;

  /**
   * @todo.
   */
  public function __construct($data, $exportableType = NULL) {
    if (isset($exportableType)) {
      $this->exportableType = $exportableType;
    }

    // This unpack is particularly naive. We should actually try to use
    // something based on ctools_export_unpack_object for defaults and
    // stuff.
    // We should also use an unpack method rather than the constructor to
    // make it more straightforward to override for specialized behavior.
    foreach ($data as $key => $value) {
      $this->$key = $value;
    }
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
  public function setIsInDatabase($status) {
    $this->exportableInDatabase = $status;
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
  public function setIsInCode($status){
    $this->exportableInCode = $status;
  }

  /**
   * @todo.
   */
  public function getExportModule() {
    return $this->exportableModule;
  }

  /**
   * @todo.
   */
  public function setExportModule($module){
    $this->exportableModule = $module;
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
