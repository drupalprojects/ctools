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
use Drupal\user\SharedTempStoreFactory;

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
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   */
  public function __construct(ControllerResolverInterface $controller_resolver, FormBuilderInterface $form_builder, ClassResolverInterface $class_resolver, SharedTempStoreFactory $tempstore) {
    parent::__construct($controller_resolver, $form_builder);
    $this->classResolver = $class_resolver;
    $this->tempstore = $tempstore;
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
    return $reflection->newInstanceArgs($arguments);
  }

}
