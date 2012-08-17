<?php

/**
 * @file
 * Definition of Drupal\ctools\Tests\CToolsObjectCacheTest.
 */

namespace Drupal\ctools\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests object cache storage.
 */
class CToolsObjectCacheTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('ctools');

  public static function getInfo() {
    return array(
      'name' => 'CTools object cache storage',
      'description' => 'Verify that objects are written, readable and lockable.',
      'group' => 'Chaos Tools Suite',
    );
  }

  public function testObjectStorage() {
    $account1 = $this->drupalCreateUser(array());
    $this->drupalLogin($account1);

    $data = array(
      'test1' => 'foobar',
    );

    ctools_include('object-cache');
    ctools_object_cache_set('testdata', 'one', $data);
    $this->assertEqual($data, ctools_object_cache_get('testdata', 'one'), 'Object cache data successfully stored');

    // TODO Test object locking somehow.
    // Object locking/testing works on session_id but simpletest uses
    // $this->session_id so can't be tested ATM.

    ctools_object_cache_clear('testdata', 'one');
    $this->assertFalse(ctools_object_cache_get('testdata', 'one'), 'Object cache data successfully cleared');

    // TODO Test ctools_object_cache_clear_all somehow...
    // ctools_object_cache_clear_all requires session_id funtionality as well.
  }
}
