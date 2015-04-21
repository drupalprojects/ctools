<?php
/**
 * Created by PhpStorm.
 * User: kris
 * Date: 4/17/15
 * Time: 10:55 AM
 */

namespace Drupal\ctools\Wizard;


use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class EntityFormWizardBase extends FormWizardBase implements EntityFormWizardInterface {

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  public function __construct(SharedTempStoreFactory $tempstore, FormBuilderInterface $builder, ClassResolverInterface $class_resolver, EventDispatcherInterface $event_dispatcher, EntityManagerInterface $entity_manager, $tempstore_id, $machine_name = NULL, $step = NULL) {
    $this->entityManager = $entity_manager;
    parent::__construct($tempstore, $builder, $class_resolver, $event_dispatcher, $tempstore_id, $machine_name, $step);
  }

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
