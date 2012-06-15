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
  public function setStatus($exportable, $new_status);

  /**
   * @todo.
   */
  public function export($exportable, $indent = '');

  /**
   * Unpacks an array of data into properties on an exportable.
   *
   * @param \Drupal\ctools\ExportableInterface $exportable
   *   The exportable to unpack the data into.
   * @param array $data
   *   An array of data to unpack onto the exportable.
   */
  public function unpack(ExportableInterface $exportable, array $data);

  /**
   * Extracts properties from an exportable.
   *
   * @param \Drupal\ctools\ExportableInterface $exportable
   *   The exportable to pack data from.
   *
   * @return
   *   An array of data from the exportable, as used by
   *   \Drupal\ctools\ExporterInterface methods.
   */
  public function pack(ExportableInterface $exportable);

  /**
   * @todo.
   */
  public function createDuplicate(ExportableInterface $exportable);

  /**
   * Provides a default list of all exportables for this type.
   *
   * @return array
   *   An array of exportable titles.
   */
  public function defaultList();
}
