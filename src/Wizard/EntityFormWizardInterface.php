<?php
/**
 * @file
 * Contains \Drupal\ctools\Wizard\EntityFormWizardInterface.
 */

namespace Drupal\ctools\Wizard;


interface EntityFormWizardInterface extends FormWizardInterface {

  /**
   * Returns the machine name of the entity type.
   *
   * @return string
   */
  public function getEntityType();

}
