<?php
/**
 * Created by PhpStorm.
 * User: kris
 * Date: 5/3/15
 * Time: 5:05 PM
 */

namespace Drupal\ctools\Wizard;


use Drupal\Core\Form\FormState;

trait WizardTrait {

  public function instantiateWizard($class, $parameters) {
    $parameters += $this->getParameters();
  }

  public function getFormState() {
    $form_state = new FormState();

  }

  abstract function getParameters();

}
