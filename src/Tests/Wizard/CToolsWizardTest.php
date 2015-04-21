<?php

/**
 * @file
 * Contains \Drupal\ctools\Tests\Wizard\CToolsWizardTest.
 */

namespace Drupal\ctools\Tests\Wizard;


use Drupal\simpletest\WebTestBase;

/**
 * Tests basic wizard functionality.
 *
 * @group ctools
 */
class CToolsWizardTest extends WebTestBase {

  public static $modules = array('ctools', 'ctools_wizard_test');

  function testWizardSteps() {
    $this->drupalGet('ctools/wizard');
    $this->assertText('Form One');
    $this->dumpHeaders = TRUE;
    $label = $this->randomMachineName();
    $edit = [
      'label' => $label,
      'id' => 'machine_name_test',
      'one' => 'test',
    ];
    $this->drupalPostForm('ctools/wizard', $edit, t('Next'));
    $this->assertText('Form Two');
    $this->drupalPostForm(NULL, [], t('Previous'));
    $this->assertFieldByName('label', $label);
    $this->assertFieldByName('id', 'machine_name_test');
    $this->assertFieldByName('one', 'test');
    $this->drupalPostForm(NULL, [], t('Next'));
    $edit = [
      'two' => 'Second test',
    ];
    $this->drupalPostForm(NULL, $edit, t('Finish'));
    $this->assertText('Value One: test');
    $this->assertText('Value Two: Second test');
  }


}