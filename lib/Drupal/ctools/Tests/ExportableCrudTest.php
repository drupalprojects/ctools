<?php

/**
 * @file
 * Definition of Drupal\ctools\Tests\ExportableCrudTest.
 */

namespace Drupal\ctools\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests exportable CRUD operations.
 */
class ExportableCrudTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('ctools_export_test');

  public static function getInfo() {
    return array(
      'name' => 'CTools exportable CRUD tests',
      'description' => 'Test the CRUD functionality for the ctools export system.',
      'group' => 'Chaos Tools Suite',
    );
  }

  protected function setUp() {
    parent::setUp();
    $this->resetAll();
  }

  /**
   * Tests CRUD operations.
   */
  function testExportable() {
    // test entity get info
    ctools_include('export');
    $exportable_type = 'ctools_export_test';
    $info = ctools_exportable_get_info($exportable_type);
    $this->assertEqual($info['key'], 'machine', 'Exportable info loaded correctly.');

    $controller = ctools_exportable_get_controller($exportable_type);
    $schema = $controller->getSchema();
    $this->assertEqual($schema['fields']['machine']['type'], 'varchar', 'Exportable schema loaded correctly.');

    // Create a new exportable from defaults.
    $expected_export = new $info['exportable class'](array(
      'machine' => '',
      'title' => 'default_title',
      'number' => 0,
      'data' => '',
    ), $exportable_type);

    $created_export = $controller->create();

    $this->assertEqual($expected_export, $created_export, 'An exportable object has been created correctly from defaults.');

    // Create a new exportable with no defaults.
    $expected_export = new $info['exportable class'](array(
      'machine' => NULL,
      'title' => NULL,
      'number' => NULL,
      'data' => NULL,
    ), $exportable_type);

    $created_export = $controller->create(array(), FALSE);

    $this->assertEqual($expected_export, $created_export, 'An exportable object has been created correctly with no defaults.');

    // Create a new exportable from data.
    $expected_export = new $info['exportable class'](array(
      'disabled' => FALSE,
      'machine' => 'created',
      'title' => 'Created exportable',
      'number' => 0,
      'data' => '',
    ), $exportable_type);

    $data = array(
      'disabled' => FALSE,
      'machine' => 'created',
      'title' => 'Created exportable',
      'number' => 0,
      'data' => '',
    );
    $created_export = $controller->create($data);

    $this->assertEqual($expected_export, $created_export, 'An exportable object has been created correctly from data.');

    $this->assertEqual($data, $created_export->pack(), 'Data has been successfully packed as an array from a created exportable.');

    $default_data = array(
      'disabled' => FALSE,
      'api_version' => 1,
      'machine' => 'default_test',
      'title' => 'Default test',
      'number' => 2,
      'data' => serialize(''),
    );

    $expected_export = new $info['exportable class']($default_data, $exportable_type);
    $expected_export->setIsInCode(TRUE);
    $expected_export->setExportModule('ctools_export_test');

    $loaded_export = $controller->load('default_test');

    $this->assertEqual($expected_export, $loaded_export, 'An exportable object has been loaded correctly from defaults.');

    // When data is packed, it will expect the unserialized empty string.
    $default_data['data'] = '';

    $this->assertEqual($default_data, $loaded_export->pack(), 'Data has been successfully packed as an array from a default exportable.');

    $loaded_export = $controller->load('default_test_disabled');

    $this->assertFalse($loaded_export->isEnabled(), 'A disabled exportable object has been loaded correctly from defaults');

    $expected_export = new $info['exportable class'](array(
      'disabled' => FALSE,
      'api_version' => 1,
      'machine' => 'database_test',
      'title' => 'Database test',
      'number' => 0,
      'data' => serialize(array(
        'test_1' => 'Test 1',
        'test_2' => 'Test 2',
      )),
    ), $exportable_type);
    $expected_export->setIsInDatabase(TRUE);
    $expected_export->setExportModule('ctools_export_test');

    $loaded_export = $controller->load('database_test');

    $this->assertEqual($expected_export, $loaded_export, 'An exportable object has been loaded correctly from the database.');

    $this->assertTrue(is_array($loaded_export->data), 'Serialized data has been unserialized on the exportable.');

    // Load an overridden exportable.
    $expected_export = new $info['exportable class'](array(
      'disabled' => FALSE,
      'api_version' => 1,
      'machine' => 'overridden_test',
      'title' => 'Overridden test (database value)',
      // Overridden value is 2, default is 1.
      'number' => 2,
      'data' => serialize(array(
        'test_1' => 'Test 1',
        'test_2' => 'Test 2',
      )),
    ), $exportable_type);
    $expected_export->setIsInDatabase(TRUE);
    $expected_export->setIsInCode(TRUE);
    $expected_export->setExportModule('ctools_export_test');

    $loaded_export = $controller->load('overridden_test');

    $this->assertEqual($expected_export, $loaded_export, 'An overridden exportable object has been loaded correctly from the database.');

    // Load all exportables.
    $all_exportables = $controller->loadAll();

    $this->assertTrue(count($all_exportables) == 4, 'All exportables have been loaded');

    // Load multiple exportables.
    $multiple_exportables = $controller->loadMultiple(array_keys($all_exportables));

    $this->assertEqual(count($multiple_exportables), 4, 'Multiple exportables have been correctly loaded.');

    // Get a default list of exportables.
    $this->assertEqual(count($controller->defaultList()), 4, 'A default list of all exportables has been loaded.');

    $this->assertTrue(array_keys($all_exportables) == array_keys($controller->defaultList()), 'The default list exportable keys match all loaded exportables.');

    // Test the default list title.
    $default_test_key = array_search('Default test (default_test)', $controller->defaultList());
    $this->assertEqual($default_test_key, 'default_test', 'The default list of exportables contain the expected titles.');

    // Disable an exportable
    $loaded_export = $controller->load('database_test');
    $loaded_export->disable();

    $this->assertFalse($loaded_export->isEnabled(), 'An exportable has been disabled.');

    // Enable an exportable
    $loaded_export = $controller->load('database_test');
    $loaded_export->enable();

    $this->assertTrue($loaded_export->isEnabled(), 'An exportable has been enabled.');

    // Save an exportable to the database.
    $default_export = $controller->load('default_test');
    $default_export->save();

    $loaded_export = $controller->load('default_test');

    $this->assertTrue($loaded_export->isInDatabase(), 'A default exportable has been saved and loaded from the database.');

    $this->assertEqual($default_export, $loaded_export, 'A saved exportable object matches it\'s default.');

    // Delete an overridden exportable from the database.
    $loaded_export->delete();

    // TODO: Replace with reloading the exportable when we can clear the caches in controller.
    $result = db_query("SELECT title from {ctools_export_test} WHERE machine = 'default_test'")->fetchField();

    $this->assertFalse($result, 'An overridden exportable has been deleted from the database and reverted to it\'s default.');

    // Delete an exportable from the database.
    $loaded_export = $controller->load('database_test');
    $loaded_export->delete();

    // TODO: Replace with reloading the exportable when we can clear the caches in controller.
    $result = db_query("SELECT title from {ctools_export_test} WHERE machine = 'database_test'")->fetchField();

    $this->assertFalse($result, 'A saved exportable has been deleted from the database.');

    // Test the creation of a Duplicate exportable.
    $default_export = $controller->load('default_test');
    $duplicate_export = $default_export->createDuplicate();

    $this->assertIdentical($default_export->pack(), $duplicate_export->pack(), 'Data packed from a duplicate export matches the original.');

    $this->assertEqual($default_export->getExportableType(), $duplicate_export->getExportableType(), 'The export type set on the duplicate export matches the original.');
  }

}
