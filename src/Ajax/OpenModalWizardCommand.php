<?php
/**
 * @file
 * Contains \Drupal\ctools\Ajax\OpenModalWizardCommand.
 */

namespace Drupal\ctools\Ajax;

use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\ctools\Event\WizardEvent;
use Drupal\ctools\Wizard\FormWizardInterface;
use Drupal\ctools\Wizard\WizardUsageTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OpenModalWizardCommand extends OpenModalDialogCommand {
  use WizardUsageTrait;

  public function __construct($class, $tempstore_id, $machine_name = NULL, $step = NULL, array $dialog_options = array(), $settings = NULL) {
    // Instantiate the wizard class properly.
    $parameters = [
      'tempstore_id' => $tempstore_id,
      'machine_name' => $machine_name,
      'step' => $step,
    ];
    $form = $this->getWizardForm($class, $parameters, TRUE, \Drupal::service('form_builder'), \Drupal::service('event_dispatcher'));
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $title = isset($form['#title']) ? $form['#title'] : '';
    $content = $form;

    parent::__construct($title, $content, $dialog_options, $settings);
  }

  /**
   * {@inheritdoc}
   */
  protected function getParameters() {
    return [
      'tempstore' => \Drupal::service('user.shared_tempstore'),
      'builder' => \Drupal::service('form_builder'),
      'class_resolver' => \Drupal::service('class_resolver'),
      'event_dispatcher' => \Drupal::service('event_dispatcher'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValues(EventDispatcherInterface $dispatcher, FormWizardInterface $wizard) {
    $values = [];
    $event = new WizardEvent($wizard, $values);
    $dispatcher->dispatch(FormWizardInterface::LOAD_VALUES, $event);
    $wizard->initValues($values);
  }

}
