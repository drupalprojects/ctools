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
  }

  /**
   * @todo.
   */
  public function delete() {
  }

  /**
   * @todo.
   */
  public function enable() {
  }

  /**
   * @todo.
   */
  public function disable() {
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
  }

  /**
   * @todo.
   */
  public function createDuplicate() {
  }

}
