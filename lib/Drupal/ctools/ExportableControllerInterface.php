<?php

/**
 * @file
 * Definition of Drupal\ctools\ExportableControllerInterface.
 */

namespace Drupal\ctools;

/**
 * Defines the controller interface for exportables.
 *
 * The Exportable controller handles the task of interfacing with the
 * backend storage for all exportables of that type. It closely resembles
 * the Entity interface, but it is specific to exportable objects.
 */
interface ExportableControllerInterface {

  /**
   * Constructs an ExportableController object.
   *
   * @param string $type
   *   The name of the exportable type. This must exist via
   *   ctools_exportable_get_info().
   * @param array $info
   *   The array of info for the exportable type, as returned by
   *   ctools_exportable_get_info().
   */
  public function __construct($type, array $info);

  /**
   * Returns the name of the exportable type.
   *
   * @return string
   *   This name of the exportable type. Used as a key in
   *   ctools_exportable_get_info().
   */
  public function getType();

  /**
   * Returns the info array of the exportable type.
   *
   * @return array
   *   The array implemented in hook_ctools_exportable_info() and returned
   *   by ctools_exportable_get_info().
   */
  public function getInfo();

  /**
   * Returns the schema array of the exportable type.
   *
   * @return array
   *   The schema defines the individual fields that will be loaded and
   *   stored. The actual schema array can vary by backend, but it should
   *   match the schema of the database implementation used as well as
   *   describe the fields that can be expected on any exportable managed by
   *   this controller.
   */
  public function getSchema();

  /**
   * Loads an exportable object.
   *
   * @param string $key
   *   The unique key of the exportable object. This is typically either a
   *   machine name (configured by user input) or a UUID (configured
   *   automatically).
   *
   * @return Drupal\ctools\ExportableInterface
   *   An object that implements the appropriate ExportableInterface for
   *   this exportable type, or NULL if the object could not be located.
   */
  public function load($key);

  /**
   * Loads a multiple exportable objects.
   *
   * @param array $keys
   *   An array of unique keys to load.
   *
   * @return array
   *   An array of objects that implement the appropriate ExportableInterface
   *   for this exportable type, keyed by the unique keys of the objects.
   *   The array should match the $keys array, but objects that could not
   *   be located will not appear.
   */
  public function loadMultiple(array $keys);

  /**
   * Loads all exportable objects.
   *
   * @return array
   *   An array of objects that implement the appropriate ExportableInterface
   *   for this exportable type.
   */
  public function loadAll();

  /**
   * Imports raw code and returns an exportable object.
   *
   * @param string $raw
   *   A string of raw code, appropriate to the exportable type.
   *
   * @return Drupal\ctools\ExportableInterface|null
   *   An instantiated exportable object, if the code could be parsed, or
   *   NULL if not.
   */
  public function import($code);

  /**
   * Creates a new exportable object from a data array.
   *
   * @param array $data
   *   (optional) An array of data that defines the exportable object, as
   *   stored or exported. If empty, an object will be created with default
   *   values. Defaults to empty.
   *
   * @return Drupal\ctools\ExportableInterface
   *   An instantiated exportable object.
   */
  public function create(array $data = array());

  /**
   * Writes an exportable object to the data store.
   *
   * @param Drupal\ctools\ExportableInterface $exportable
   *   An exportable object to be saved into the data store.
   */
  public function save(ExportableInterface $exportable);

  /**
   * Deletes multiple exportable objects by key.
   *
   * @param array $keys
   *   An array of keys to objects.
   */
  public function delete(array $keys);

  /**
   * Enables the exportable object.
   *
   * @param Drupal\ctools\ExportableInterface $exportable
   *    An exportable object to be enabled.
   *
   * @see Drupal\ctools\ExportableControllerInterface::setStatus()
   */
  public function enable(ExportableInterface $exportable);

  /**
   * Disables the exportable object.
   *
   * @param Drupal\ctools\ExportableInterface $exportable
   *   An exportable object to be disabled.
   *
   * @see Drupal\ctools\ExportableControllerInterface::setStatus()
   */
  public function disable(ExportableInterface $exportable);

  /**
   * Sets the enabled/disabled status of an exportable object.
   *
   * The enable() and disable() methods are wrappers for this method.
   *
   * @param Drupal\ctools\ExportableInterface $exportable
   *   An exportable object to have its status updated.
   * @param bool $new_status
   *   The new status to set. TRUE to be disabled and FALSE to be enabled.
   *
   * @see Drupal\ctools\ExportableControllerInterface::enable()
   * @see Drupal\ctools\ExportableControllerInterface::disable()
   */
  public function setStatus(ExportableInterface $exportable, $new_status);

  /**
   * Exports an exportable object into raw code.
   *
   * @param Drupal\ctools\ExportableInterface $exportable
   *   An exportable object to be exported.
   * @param string $indent
   *   (optional) String to indent each line of the exportable. This can be
   *   used to ensure that the code visually lines up when one exportable
   *   contains another. Defaults to an empty string.
   *
   * @return string
   *   The exported code suitable for importing via import() or embedding
   *   into code.
   */
  public function export(ExportableInterface $exportable, $indent = '');

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
   * @param Drupal\ctools\ExportableInterface $exportable
   *   The exportable to pack data from.
   *
   * @return
   *   An array of data from the exportable, as used by
   *   Drupal\ctools\ExporterInterface methods.
   */
  public function pack(ExportableInterface $exportable);

  /**
   * Creates a clean copy of an exportable object.
   *
   * This object will have any database-specific IDs scrubbed, but it
   * will probably need its unique identifier modified as appropriate.
   *
   * @param Drupal\ctools\ExportableInterface $exportable
   *   An exportable object to be cloned.
   *
   * @return Drupal\ctools\ExportableInterface
   *   A clean copy of the exportable object.
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
