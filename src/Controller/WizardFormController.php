<?php

/**
 * @file
 * Contains \Drupal\ctools\Controller\WizardFormController.
 */

namespace Drupal\ctools\Controller;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Controller\FormController;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\ctools\Event\WizardEvent;
use Drupal\ctools\Wizard\FormWizardInterface;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Wrapping controller for wizard forms that serve as the main page body.
 */
class WizardFormController extends FormController {

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface;
   */
  protected $classResolver;

  /**
   * Tempstore Factory for keeping track of values in each step of the wizard.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   Tempstore Factory for keeping track of values in each step of the
   *   wizard.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, FormBuilderInterface $form_builder, ClassResolverInterface $class_resolver, SharedTempStoreFactory $tempstore, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($controller_resolver, $form_builder);
    $this->classResolver = $class_resolver;
    $this->tempstore = $tempstore;
    $this->dispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormArgument(RouteMatchInterface $route_match) {
    return $route_match->getRouteObject()->getDefault('_wizard');
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormObject(RouteMatchInterface $route_match, $form_arg) {
    $class = $this->getFormArgument($route_match);
    $parameters = $route_match->getParameters()->all();
    $parameters += [
      'tempstore' => $this->tempstore,
      'builder' => $this->formBuilder,
      'class_resolver' => $this->classResolver,
      'event_dispatcher' => $this->dispatcher,
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
    $this->dispatcher->dispatch(FormWizardInterface::LOAD_VALUES, $event);
    $instance->initValues($event->getValues());
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentResult(Request $request, RouteMatchInterface $route_match) {
    $form_arg = $this->getFormArgument($route_match);
    $form_object = $this->getFormObject($route_match, $form_arg);

    // Add the form and form_state to trick the getArguments method of the
    // controller resolver.
    $form_state = new FormState();

    // Get the cached values out of the tempstore.
    $ajax = $route_match->getRouteObject()->getDefault('js') == 'ajax' ? TRUE : FALSE;
    $cached_values = $form_object->getTempstore()->get($form_object->getMachineName());
    $form_state->setTemporaryValue('wizard', $cached_values);
    $form_state->set('ajax', $ajax);

    $request->attributes->set('form', []);
    $request->attributes->set('form_state', $form_state);
    $args = $this->controllerResolver->getArguments($request, [$form_object, 'buildForm']);
    $request->attributes->remove('form');
    $request->attributes->remove('form_state');

    // Remove $form and $form_state from the arguments, and re-index them.
    unset($args[0], $args[1]);
    $form_state->addBuildInfo('args', array_values($args));

    $form = $this->formBuilder->buildForm($form_object, $form_state);
    return $form;
  }

}
