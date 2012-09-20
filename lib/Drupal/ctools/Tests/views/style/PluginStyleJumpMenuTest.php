<?php

/**
 * @file
 * Definition of Drupal\ctools\Tests\views\style\PluginStyleJumpMenuTest.
 */

namespace Drupal\ctools\Tests\views\style;

use Drupal\views\Tests\ViewTestBase;

/**
 * Tests jump menu style functionality.
 */
class PluginStyleJumpMenuTest extends ViewTestBase {


  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('ctools_views_test');

  public static function getInfo() {
    return array(
      'name' => 'Jump menu',
      'description' => 'Test jump menu style functionality.',
      'group' => 'Chaos Tools Suite: Views',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->nodes = array();
    $this->nodes['page'][] = $this->drupalCreateNode(array('type' => 'page'));
    $this->nodes['page'][] = $this->drupalCreateNode(array('type' => 'page'));
    $this->nodes['story'][] = $this->drupalCreateNode(array('type' => 'story'));
    $this->nodes['story'][] = $this->drupalCreateNode(array('type' => 'story'));

    $this->nodeTitles = array($this->nodes['page'][0]->label(), $this->nodes['page'][1]->label(), $this->nodes['story'][0]->label(), $this->nodes['story'][1]->label());
  }

  /**
   * Tests jump menues with more then one same path but maybe differnet titles.
   */
  function testDuplicatePaths() {
    $view = $this->getView();
    $view->setDisplay();
    $view->initHandlers();

    // Setup a [path] which would leed to "duplicate" paths, but still the shouldn't be used for grouping.
    $view->field['nothing']->options['alter']['text'] = '[path]';
    $view->preview();
    $form = $view->style_plugin->render($view->result);

    // As there is no grouping setup it should be 4 elements.
    $this->assertEqual(count($form['jump']['#options']), 4 + 1);

    // Check that all titles are part of the form as well.
    $options = array_values($form['jump']['#options']);
    foreach ($options as $key => $title) {
      // The first one is the choose label.
      if ($key == 0) {
        continue;
      }
      $this->assertEqual($this->nodeTitles[$key - 1], trim($title), format_string('Title @title should appear on the jump list, as we do not filter', array('@title' => $title)));
    }
  }

  /**
   * Overrides Drupal\views\Tests\ViewTestBase::getBasicView().
   */
  protected function getBasicView() {
    return $this->createViewFromConfig('test_jump_menu');
  }

}
