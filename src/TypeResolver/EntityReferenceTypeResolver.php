<?php
/**
 * @file
 * Contains \Drupal\ctools\TypeResolver\EntityReferenceTypeResolver.
 */

namespace Drupal\ctools\TypeResolver;


use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\ctools\TypeResolverInterface;

class EntityReferenceTypeResolver implements TypeResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function getDataTypeFromProperty(ListDataDefinitionInterface $property) {
    return $property->getItemDefinition()->getPropertyDefinition('entity')->getTargetDefinition()->getDataType();
  }

  /**
   * {@inheritdoc}
   */
  public function applies($type) {
    return $type == 'entity_reference';
  }

  /**
   * {@inheritdoc}
   */
  public function appliesToProperty(ListDataDefinitionInterface $property) {
    return $property->getType() == 'entity_reference';
  }

  /**
   * {@inheritdoc}
   */
  public function getValueFromProperty(FieldItemListInterface $property, $delta = 0) {
    if ($property->getValue() && $property->getFieldDefinition()->getCardinality() == 1) {
      return $property->referencedEntities()[0];
    }
    return $property->referencedEntities();
  }

}
