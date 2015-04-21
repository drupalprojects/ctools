<?php
/**
 * Created by PhpStorm.
 * User: kris
 * Date: 4/21/15
 * Time: 12:10 AM
 */

namespace Drupal\ctools\Event;


use Drupal\ctools\Wizard\FormWizardInterface;
use Symfony\Component\EventDispatcher\Event;

class WizardEvent extends Event {

  /**
   * @var \Drupal\ctools\Wizard\FormWizardInterface
   */
  protected $wizard;

  /**
   * @var mixed
   */
  protected $values;

  function __construct(FormWizardInterface $wizard, $values) {
    $this->wizard = $wizard;
    $this->values = $values;
  }

  public function getWizard() {
    return $this->wizard;
  }

  public function getValues() {
    return $this->values;
  }

  public function setValues($values) {
    $this->values = $values;
    return $this;
  }

}