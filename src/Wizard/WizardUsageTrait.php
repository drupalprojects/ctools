<?php
/**
 * @file
 * Contains \Drupal\ctools\Wizard\WizardTrait.
 */

namespace Drupal\ctools\Wizard;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

trait WizardUsageTrait {

  /**
   * Get the wizard form.
   *
   * @param string $class
   *   A class name implementing FormWizardInterface.
   * @param array $parameters
   *   The array of default parameters specific to this wizard.
   * @param bool $ajax
   *   Whether or not this wizard is displayed via ajax modals.
   * @param \Drupal\Core\Form\FormBuilderInterface $builder
   *   The form builder.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   *
   * @return array
   */
  public function getWizardForm($class, array $parameters = array(), $ajax = FALSE, FormBuilderInterface $builder, EventDispatcherInterface $dispatcher) {
    $parameters += $this->getParameters();
    $wizard = $this->instantiateWizard($class, $parameters);
    $this->prepareValues($dispatcher, $wizard);
    $form_state = $this->getFormState($wizard, $parameters, $ajax);
    return $builder->buildForm($wizard, $form_state);
  }

  /**
   * @param string $class
   *   A class name implementing FormWizardInterface.
   * @param array $parameters
   *   The array of parameters specific to this wizard.
   *
   * @return \Drupal\ctools\Wizard\FormWizardInterface
   */
  public function instantiateWizard($class, array $parameters) {
    $arguments = [];
    $reflection = new \ReflectionClass($class);
    $constructor = $reflection->getMethod('__construct');
    foreach ($constructor->getParameters() as $parameter) {
      if (array_key_exists($parameter->name, $parameters)) {
        $arguments[] = $parameters[$parameter->name];
      }
      elseif ($parameter->isDefaultValueAvailable()) {
        $arguments[] = $parameter->getDefaultValue();
      }
    }
    /** @var $wizard \Drupal\ctools\Wizard\FormWizardInterface */
    $wizard = $reflection->newInstanceArgs($arguments);
    return $wizard;
  }

  /**
   * Get the wizard form state.
   *
   * @param \Drupal\ctools\Wizard\FormWizardInterface $wizard
   *   The form wizard.
   * @param array $parameters
   *   The array of parameters specific to this wizard.
   * @param bool $ajax
   *
   * @return \Drupal\Core\Form\FormState
   */
  public function getFormState(FormWizardInterface $wizard, array $parameters, $ajax = FALSE) {
    $form_state = new FormState();
    $cached_values = $wizard->getTempstore()->get($wizard->getMachineName());
    $form_state->setTemporaryValue('wizard', $cached_values);
    $form_state->set('ajax', $ajax);

    $parameters['form'] = [];
    $parameters['form_state'] = $form_state;
    $method = new \ReflectionMethod($wizard, 'buildForm');
    $arguments = [];
    foreach ($method->getParameters() as $parameter) {
      if (array_key_exists($parameter->name, $parameters)) {
        $arguments[] = $parameters[$parameter->name];
      }
      elseif ($parameter->isDefaultValueAvailable()) {
        $arguments[] = $parameter->getDefaultValue();
      }
    }
    unset($parameters['form'], $parameters['form_state']);
    // Remove $form and $form_state from the arguments, and re-index them.
    unset($arguments[0], $arguments[1]);
    $form_state->addBuildInfo('args', array_values($arguments));
    return $form_state;
  }

  /**
   * Return the wizard type specific parameters.
   *
   * @return array
   */
  abstract protected function getParameters();

  /**
   * Prepare and load values for the wizard.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param \Drupal\ctools\Wizard\FormWizardInterface $wizard
   *   The form wizard.
   *
   * @return void
   */
  abstract protected function prepareValues(EventDispatcherInterface $dispatcher, FormWizardInterface $wizard);

}
