<?php

/**
 * @file
 * Definition of Drupal\ctools\Exportable.
 */

namespace Drupal\ctools;

/**
 * @todo.
 */
class Exportable implements ExportableInterface {

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
   * Stores the disabled state of the exportable.
   *
   * @var bool
   */
  protected $disabled = FALSE;

  /**
   * @todo.
   */
  public function __construct($data, $exportableType = NULL) {
    if (isset($exportableType)) {
      $this->exportableType = $exportableType;
    }

    $disabled = FALSE;

    if (isset($data['disabled'])) {
      $disabled = $data['disabled'];
      unset($data['disabled']);
    }

    $this->unpack($data);

    $info = ctools_exportable_get_controller($this->exportableType)->getInfo();
    $status = variable_get($info['status'], array());
    $this->disabled = isset($status[$this->id()]) ? $status[$this->id()] : $disabled;
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
    $this->disabled = FALSE;
    ctools_exportable_get_controller($this->exportableType)->enable($this);
  }

  /**
   * @todo.
   */
  public function disable() {
    $this->disabled = TRUE;
    ctools_exportable_get_controller($this->exportableType)->disable($this);
  }

  /**
   * Implements Drupal\ctools\ExportableInterface::isEnabled().
   */
  public function isEnabled() {
    return !$this->disabled;
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
   * Implements \Drupal\ctools\ExportableInterface::getExportableType().
   */
  public function getExportableType() {
    return $this->exportableType;
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
    return ctools_exportable_get_controller($this->exportableType)->export($this, $indent);
  }

  /**
   * @todo.
   */
  public function createDuplicate() {
    return ctools_exportable_get_controller($this->exportableType)->createDuplicate($this);
  }

  /**
   * @todo.
   */
  public function id() {
    $info = ctools_exportable_get_controller($this->exportableType)->getInfo();
    return $this->{$info['key']};
  }

  /**
   * Implements Drupal\ctools\ExportableInterface::title().
   */
  public function title() {
    $info = ctools_exportable_get_controller($this->exportableType)->getInfo();
    return $this->{$info['title key']};
  }

}
