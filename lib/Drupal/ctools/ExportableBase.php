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

    $this->unpack($data);
  }

  /**
   * Extracts properties from the exportable as an array of data.
   */
  public function pack() {
    return ctools_exportable_get_controller($this->exportableType)->pack($this);
  }

  /**
   * Unpacks an array of data as properties on the exportable.
   */
  public function unpack($data) {
    ctools_exportable_get_controller($this->exportableType)->unpack($this, $data);
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

  /**
   * @todo.
   */
  public function id() {
    $info = ctools_exportable_get_controller($this->exportableType)->getInfo();
    return $this->{$info['key']};
  }

}
