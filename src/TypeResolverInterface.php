<?php
/**
 * @file
 * Contains \Drupal\ctools\TypeResolverInterface.
 */

namespace Drupal\ctools;


use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;

interface TypeResolverInterface {

  /**
   * Extracts the appropriate data type information from a property.
   *
   * For an entity_reference field, you cannot simply ask the field for its
   * data type because that is "entity_reference". The useful data is that it
   * is referencing entity:user entities. This method is an abstraction
   * designed to allow for that sort of interaction to occur without developers
   * needing to know all the relevant nuance to the property api.
   *
   * @param \Drupal\Core\TypedData\ListDataDefinitionInterface $property
   *   The property from which to extract a data type.
   *
   * @return string
   *   The data type the property is referencing.
   */
  public function getDataTypeFromProperty(ListDataDefinitionInterface $property);

  /**
   * A simple boolean check for whether a type resolver applies to an object.
   *
   * @param mixed $type
   *   The value to evaluate.
   *
   * @return bool
   */
  public function applies($type);

  /**
   * A property specific implementation of the applies() method.
   *
   * @param \Drupal\Core\TypedData\ListDataDefinitionInterface $property
   *   The property to evaluate.
   *
   * @return bool
   *
   * @see \Drupal\ctools\TypeResolverInterface::applies()
   */
  public function appliesToProperty(ListDataDefinitionInterface $property);

  /**
   * Appropriately extracts the value of a property.
   *
   * The property API does not have a consistent pattern for extracting the
   * relevant data for a need. Language properties return a langcode even
   * though they have methods to return full language objects. The same is true
   * of entity_references. This method allows for the useful value to be
   * extracted from a field and returned.
   *
   * The value returned from this method should correspond to the data type
   * returned by getDataTypeFromProperty().
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $property
   *
   * @return mixed
   *
   * @see \Drupal\ctools\TypeResolverInterface::getDataTypeFromProperty()
   */
  public function getValueFromProperty(FieldItemListInterface $property);

}
