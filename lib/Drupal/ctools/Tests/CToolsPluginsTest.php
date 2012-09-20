<?php

/**
 * @file
 * Definition of Drupal\ctools\Tests\CToolsPluginsTest.
 */

namespace Drupal\ctools\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test menu links depending on user permissions.
 */
class CToolsPluginsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('ctools_plugin_test');

  public static function getInfo() {
    return array(
      'name' => 'Get plugin info',
      'description' => 'Verify that plugin type definitions can properly set and overide values.',
      'group' => 'Chaos Tools Suite',
    );
  }

  protected function assertPluginFunction($module, $type, $id, $function = 'function') {
    $func = ctools_plugin_load_function($module, $type, $id, $function);
    $this->assertTrue(function_exists($func), t('Plugin @plugin of plugin type @module:@type successfully retrieved @retrieved for @function.', array(
      '@plugin' => $id,
      '@module' => $module,
      '@type' => $type,
      '@function' => $function,
      '@retrieved' => $func,
    )));
  }

  protected function assertPluginMissingFunction($module, $type, $id, $function = 'function') {
    $func = ctools_plugin_load_function($module, $type, $id, $function);
    $this->assertEqual($func, NULL, t('Plugin @plugin of plugin type @module:@type for @function with missing function successfully failed.', array(
      '@plugin' => $id,
      '@module' => $module,
      '@type' => $type,
      '@function' => $func,
    )));
  }

  protected function assertPluginMissingClass($module, $type, $id, $class = 'handler') {
    $class_name = ctools_plugin_load_class($module, $type, $id, $class);
    $this->assertEqual($class_name, NULL, t('Plugin @plugin of plugin type @module:@type for @class with missing class successfully failed.', array(
      '@plugin' => $id,
      '@module' => $module,
      '@type' => $type,
      '@class' => $class,
    )));
  }

  /**
   * Test that plugins are loaded correctly.
   */
  function testPluginLoading() {
    ctools_include('plugins');
    $module = 'ctools_plugin_test';
    $type = 'not_cached';

    // Test function retrieval for plugins using different definition methods.
    $this->assertPluginFunction($module, $type, 'plugin_array', 'function');
    $this->assertPluginFunction($module, $type, 'plugin_array2', 'function');
    $this->assertPluginMissingFunction($module, $type, 'plugin_array_dne', 'function');
    $this->assertPluginFunction($module, "big_hook_$type", 'test1', 'function');

    $type = 'cached';

    // Test function retrieval for plugins using different definition methods.
    $this->assertPluginFunction($module, $type, 'plugin_array', 'function');
    $this->assertPluginFunction($module, $type, 'plugin_array2', 'function');
    $this->assertPluginMissingFunction($module, $type, 'plugin_array_dne', 'function');
    $this->assertPluginFunction($module, "big_hook_$type", 'test1', 'function');
  }

}
