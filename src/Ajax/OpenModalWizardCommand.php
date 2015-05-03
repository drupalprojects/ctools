<?php
/**
 * @file
 * Contains \Drupal\ctools\Ajax\OpenModalWizardCommand.
 */

namespace Drupal\ctools\Ajax;

use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormState;
use Drupal\ctools\Event\WizardEvent;
use Drupal\ctools\Wizard\FormWizardInterface;

class OpenModalWizardCommand extends OpenModalDialogCommand {
  public function __construct($class, $tempstore_id, $machine_name = NULL, $step = NULL, array $dialog_options = array(), $settings = NULL) {
    // Instantiate the wizard class properly.
    $parameters = [
      'tempstore' => \Drupal::service('user.shared_tempstore'),
      'builder' => \Drupal::service('form_builder'),
      'class_resolver' => \Drupal::service('class_resolver'),
      'event_dispatcher' => \Drupal::service('event_dispatcher'),
      'tempstore_id' => $tempstore_id,
      'machine_name' => $machine_name,
      'step' => $step,
    ];
    $arguments = [];
    $reflection = new \ReflectionClass($class);
    $constructor = $reflection->getMethod('__construct');
    foreach ($constructor->getParameters() as $parameter) {
      $name = $parameter->getName();
      if (!empty($parameters[$name])) {
        $arguments[] = $parameters[$name];
      }
    }
    /** @var $instance \Drupal\ctools\Wizard\FormWizardInterface */
    $instance = $reflection->newInstanceArgs($arguments);
    $values = [];
    $event = new WizardEvent($instance, $values);
    $parameters['event_dispatcher']->dispatch(FormWizardInterface::LOAD_VALUES, $event);
    $instance->initValues($event->getValues());

    // Retrieve the content of the wizard.
    $form_state = new FormState();

    // Get the cached values out of the tempstore.
    $cached_values = $parameters['tempstore']->get($tempstore_id)->get($machine_name);
    $form_state->setTemporaryValue('wizard', $cached_values);
    $form_state->set('ajax', TRUE);

    // @todo get reflection on the buildForm() method working.
    //unset($args[0], $args[1]);
    //$form_state->addBuildInfo('args', array_values($args));

    $form = $parameters['builder']->buildForm($instance, $form_state);
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $title = isset($form['#title']) ? $form['#title'] : '';
    $content = $form;

    parent::__construct($title, $content, $dialog_options, $settings);
  }

}