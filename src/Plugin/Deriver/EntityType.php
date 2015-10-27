<?php
/**
 * @file
 * Contains \Drupal\ctools\Plugin\Deriver\EntityType
 */

namespace Drupal\ctools\Plugin\Deriver;


use Drupal\Core\Plugin\Context\ContextDefinition;

class EntityType extends EntityDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      $bundle_of = $entity_type->getBundleOf();
      if ($bundle_of) {
        $this->derivatives[$entity_type_id] = $base_plugin_definition;
        $this->derivatives[$entity_type_id]['label'] = $this->t('@label', ['@label' => $entity_type->getLabel()]);
        $this->derivatives[$entity_type_id]['context'] = [
          "$bundle_of" => new ContextDefinition('entity:' . $bundle_of),
        ];
      }
    }
    return $this->derivatives;
  }

}
