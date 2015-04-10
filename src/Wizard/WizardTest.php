<?php
/**
 * @file
 * Contains \Drupal\ctools\Wizard\WizardTest.
 */

namespace Drupal\ctools\Wizard;


use Drupal\Core\Form\FormStateInterface;

class WizardTest extends FormWizardBase {

  public function getWizardLabel() {
    return $this->t('Wizard Information');
  }

  public function getMachineLabel() {
    return $this->t('Wizard Test Name');
  }

  /**
   * A list of FormInterface classes keyed by their step in the wizard.
   *
   * @return array
   */
  public function getOperations() {
    return array(
      'one' => 'Drupal\ctools\Form\OneForm',
      'two' => 'Drupal\ctools\Form\TwoForm',
    );
  }

  public function getRouteName() {
    return 'ctools.wizard.step';
  }

  public function finish(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->get('wizard');
    drupal_set_message($this->t('Value One: @one', ['@one' => $cached_values['one']]));
    drupal_set_message($this->t('Value Two: @two', ['@two' => $cached_values['two']]));
    parent::finish($form, $form_state);
  }
}
