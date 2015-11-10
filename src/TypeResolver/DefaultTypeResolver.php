<?php
/**
 * @file
 * Contains \Drupal\ctools\TypeResolver\DefaultTypeResolver
 */

namespace Drupal\ctools\TypeResolver;


use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\ctools\TypeResolverInterface;

class DefaultTypeResolver implements TypeResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function getDataTypeFromProperty(ListDataDefinitionInterface $property) {
    return $property->getType();
  }

  /**
   * {@inheritdoc}
   */
  public function applies($type) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesToProperty(ListDataDefinitionInterface $property) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueFromProperty(FieldItemListInterface $property) {
    return $property->getValue();
  }

}
