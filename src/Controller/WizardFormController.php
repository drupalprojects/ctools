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
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\ctools\Event\WizardEvent;
use Drupal\ctools\Wizard\FormWizardInterface;
use Drupal\ctools\Wizard\WizardUsageTrait;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Wrapping controller for wizard forms that serve as the main page body.
 */
class WizardFormController extends FormController {
  use WizardUsageTrait;

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
   * Wizards are not instantiated as simply as forms, so this method is unused.
   */
  protected function getFormObject(RouteMatchInterface $route_match, $form_arg) {}

  /**
   * {@inheritdoc}
   */
  public function getContentResult(Request $request, RouteMatchInterface $route_match) {
    $class = $this->getFormArgument($route_match);
    $parameters = $route_match->getParameters()->all();
    $ajax = $request->attributes->get('js') == 'ajax' ? TRUE : FALSE;

    return $this->getWizardForm($class, $parameters, $ajax, $this->formBuilder, $this->dispatcher);
  }

  protected function prepareValues(EventDispatcherInterface $dispatcher, FormWizardInterface $wizard) {
    $values = [];
    $event = new WizardEvent($wizard, $values);
    $dispatcher->dispatch(FormWizardInterface::LOAD_VALUES, $event);
    $wizard->initValues($values);
  }

  protected function getParameters() {
    return [
      'tempstore' => $this->tempstore,
      'builder' => $this->formBuilder,
      'class_resolver' => $this->classResolver,
      'event_dispatcher' => $this->dispatcher,
    ];
  }

}
