<?php

/**
 * @file
 * Contains \Drupal\ctools\Controller\WizardEntityFormController.
 */

namespace Drupal\ctools\Controller;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Controller\FormController;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\ctools\Event\WizardEvent;
use Drupal\ctools\Wizard\FormWizardInterface;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Wrapping controller for wizard forms that serve as the main page body.
 */
class WizardEntityFormController extends FormController {

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
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

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
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, FormBuilderInterface $form_builder, ClassResolverInterface $class_resolver, SharedTempStoreFactory $tempstore, EventDispatcherInterface $event_dispatcher, EntityManagerInterface $manager) {
    parent::__construct($controller_resolver, $form_builder);
    $this->classResolver = $class_resolver;
    $this->tempstore = $tempstore;
    $this->dispatcher = $event_dispatcher;
    $this->entityManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormArgument(RouteMatchInterface $route_match) {
    return $route_match->getRouteObject()->getDefault('_entity_wizard');
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormObject(RouteMatchInterface $route_match, $form_arg) {
    list($entity_type_id, $operation) = explode('.', $form_arg);
    $definition = $this->entityManager->getDefinition($entity_type_id);
    $handlers = $definition->getHandlerClasses();
    if (empty($handlers['wizard'][$operation])) {
      throw new \Exception(sprintf('Unsupported wizard operation %s', $operation));
    }
    $class = $handlers['wizard'][$operation];
    $parameters = $route_match->getParameters()->all();
    $parameters += [
      'tempstore' => $this->tempstore,
      'builder' => $this->formBuilder,
      'class_resolver' => $this->classResolver,
      'entity_manager' => $this->entityManager,
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
    /** @var $instance \Drupal\ctools\Wizard\EntityFormWizardInterface */
    $instance = $reflection->newInstanceArgs($arguments);
    $values = [];
    if (isset($parameters['machine_name'])) {
      $entity = $this->entityManager->getStorage($instance->getEntityType())->load($parameters['machine_name']);
      if ($entity) {
        $values = $entity->toArray();
      }
    }
    $event = new WizardEvent($instance, $values);
    $this->dispatcher->dispatch(FormWizardInterface::LOAD_VALUES, $event);
    $instance->initValues($event->getValues());
    return $instance;
  }

}
