<?php

/**
 * @file
 * Definition of Drupal\ctools\Tests\views\field\FieldMathTest.
 */

namespace Drupal\ctools\Tests\views\field;

use Drupal\views\Tests\ViewTestBase;

/**
 * Tests the core Drupal\ctools\Plugin\views\field\Math handler.
 */
class FieldMathTest extends ViewTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Field: Math',
      'description' => 'Test the core Drupal\views\Plugin\views\field\Math handler.',
      'group' => 'Views Handlers',
    );
  }

  function viewsData() {
    $data = parent::viewsData();
    return $data;
  }

  public function testFieldCustom() {
    $view = $this->getBasicView();

    // Alter the text of the field to a random string.
    $rand1 = rand(0, 100);
    $rand2 = rand(0, 100);
    $view->display['default']->handler->override_option('fields', array(
      'expression' => array(
        'id' => 'expression',
        'table' => 'views',
        'field' => 'expression',
        'relationship' => 'none',
        'expression' => $rand1 . ' + ' . $rand2,
      ),
    ));

    $this->executeView($view);

    $this->assertEqual($rand1 + $rand2, $view->style_plugin->get_field(0, 'expression'));
  }
}
