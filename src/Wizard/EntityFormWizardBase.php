<?php

/**
 * @file
 * Contains \Drupal\ctools\Wizard\EntityFormWizardBase.
 */

namespace Drupal\ctools\Wizard;


use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The base class for all entity form wizards.
 */
abstract class EntityFormWizardBase extends FormWizardBase implements EntityFormWizardInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   Tempstore Factory for keeping track of values in each step of the
   *   wizard.
   * @param \Drupal\Core\Form\FormBuilderInterface $builder
   *   The Form Builder.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param $tempstore_id
   *   The shared temp store factory collection name.
   * @param null $machine_name
   *   The SharedTempStore key for our current wizard values.
   * @param null $step
   *   The current active step of the wizard.
   */
  public function __construct(SharedTempStoreFactory $tempstore, FormBuilderInterface $builder, ClassResolverInterface $class_resolver, EventDispatcherInterface $event_dispatcher, EntityManagerInterface $entity_manager, $tempstore_id, $machine_name = NULL, $step = NULL) {
    $this->entityManager = $entity_manager;
    parent::__construct($tempstore, $builder, $class_resolver, $event_dispatcher, $tempstore_id, $machine_name, $step);
  }

  /**
   * {@inheritdoc}
   */
  public function finish(array &$form, FormStateInterface $form_state) {
    $entity = $this->entityManager->getStorage($this->getEntityType())->load($this->machine_name);
    if (!$entity) {
      $entity = $this->entityManager->getStorage($this->getEntityType())->create([]);
    }
    foreach ($form_state->get('wizard') as $key => $value) {
      $entity->set($key, $value);
    }
    $status = $entity->save();
    $definition = $this->entityManager->getDefinition($this->getEntityType());
    if ($status) {
      drupal_set_message($this->t('Saved the %label !entity_type.', array(
        '%label' => $entity->label(),
        '!entity_type' => $definition->getLabel(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label !entity_type was not saved.', array(
        '%label' => $entity->label(),
        '!entity_type' => $definition->getLabel(),
      )));
    }
    $form_state->setRedirectUrl($entity->urlInfo('collection'));
    parent::finish($form, $form_state);
  }

}
