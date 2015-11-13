<?php
/**
 * @file
 * Contains \Drupal\ctools\TypedDataResolver.
 */

namespace Drupal\ctools;


use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;

class TypedDataResolver {

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $manager;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translation;

  /**
   * The unsorted array of resolvers.
   *
   * @var \Drupal\ctools\TypeResolverInterface[]
   */
  protected $resolvers;

  /**
   * An array of resolvers sorted by priority.
   *
   * @var \Drupal\ctools\TypeResolverInterface[]|NULL
   */
  protected $sortedResolvers;

  /**
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $manager
   *   The typed data manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   */
  public function __construct(TypedDataManagerInterface $manager, TranslationInterface $translation) {
    $this->manager = $manager;
    $this->translation = $translation;
  }

  /**
   * A method used by the service container to add new type resolvers.
   *
   * @param \Drupal\ctools\TypeResolverInterface $resolver
   *   A resolver to add to the available typed data resolvers.
   * @param int $priority
   *   The resolvers priority in relation to other resolvers.
   *   Higher priority takes precedence.
   */
  public function addTypeResolver(TypeResolverInterface $resolver, $priority = 0) {
    $this->resolvers[$priority][] = $resolver;
    $this->sortedResolvers = NULL;
  }

  /**
   * Converts a ContextInterface object into a TypedDataInterface object.
   *
   * This method respects values on the Context object and will ensure they're
   * maintained on the returned typed data object.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface $context
   *   The context object to convert to a typed data object.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   A typed data representation of the supplied context object.
   */
  public function getTypedDataFromContext(ContextInterface $context) {
    $data_type = $context->getContextDefinition()->getDataType();
    $definition = $this->manager->createDataDefinition($data_type);
    $typed_data = $context->hasContextValue() ? $this->manager->create($definition, $context->getContextValue()) : $typed_data = $this->manager->create($definition);
    return $typed_data;
  }

  /**
   * Gets the contextually relevant data type of a property.
   *
   * Properties may not return a useful data type when they reference a
   * subsequent object such as language, date or entity_reference fields. This
   * method allows for another set of classes to be involved in the decision of
   * what data type to return from a property. In the case of entity_reference,
   * instead of returning "entity_reference" as the data type, this will return
   * the data type of the thing it references, such as entity:user.
   *
   * @param \Drupal\Core\TypedData\ListDataDefinitionInterface $property
   *   The property to extract an appropriate contextually relevant data type.
   *
   * @return string
   *   The data type of the data held by the property.
   */
  public function getDataTypeFromProperty(ListDataDefinitionInterface $property) {
    $resolver = $this->getFirstApplicableTypeResolver($property);
    return $resolver->getDataTypeFromProperty($property);
  }

  /**
   * Convert a property to a context.
   *
   * This method will respect the value of contexts as well, so if a context
   * object is pass that contains a value, the appropriate value will be
   * extracted and injected into the resulting context object if available.
   *
   * @param string $property_name
   *   The name of the property.
   * @param \Drupal\Core\TypedData\ListDataDefinitionInterface $property
   *   The property object data definition.
   * @param \Drupal\Core\Plugin\Context\ContextInterface $context
   *   The context from which we will extract values if available.
   *
   * @return \Drupal\Core\Plugin\Context\Context
   *   A context object that represents the definition & value of the property.
   */
  public function getContextFromProperty($property_name, ListDataDefinitionInterface $property, ContextInterface $context) {
    $value = NULL;
    if ($context->hasContextValue()) {
      $value = $this->getFirstApplicableTypeResolver($property)->getValueFromProperty($context->getContextValue()->$property_name);
    }
    $context_definition = new ContextDefinition($this->getDataTypeFromProperty($property));
    return new Context($context_definition, $value);
  }

  /**
   * Extracts a context from an array of contexts by a tokenized pattern.
   *
   * This is more than simple isset/empty checks on the contexts array. The
   * pattern could be node:uid:name which will iterate over all provided
   * contexts in the array for one named 'node', it will then load the data
   * definition of 'node' and check for a property named 'uid'. This will then
   * set a new (temporary) context on the array and recursively call itself to
   * navigate through related properties all the way down until the request
   * property is located. At that point the property is passed to a
   * TypedDataResolver which will convert it to an appropriate ContextInterface
   * object.
   *
   * @param $token
   *   A ":" delimited set of tokens representing
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   The array of available contexts.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface
   *   The requested token as a full Context object.
   *
   * @throws \Drupal\ctools\ContextNotFoundException
   */
  public function convertTokenToContext($token, $contexts) {
    // If the requested token is already a context, just return it.
    if (isset($contexts[$token])) {
      return $contexts[$token];
    }
    else {
      // Find the base context id and property, sub token is optional, so make
      // sure we have enough array elements.
      list($base, $property_name, $subtoken) = array_merge(explode(':', $token, 3), array(NULL));
      // A base must always be set. This method recursively calls itself
      // setting bases for this reason.
      if (!empty($contexts[$base])) {
        // createDataDefinition returns objects that implement this interface,
        // but that is not immediately obvious without a lot of digging.
        /** @var \Drupal\Core\TypedData\ComplexDataDefinitionInterface $context */
        $context = $this->manager->createDataDefinition($contexts[$base]->getContextDefinition()
          ->getDataType());
        /**
         * @var \Drupal\Core\Field\BaseFieldDefinition $property
         */
        $property = $context->getPropertyDefinition($property_name);
        if ($property) {
          // Let's just add this to the contexts array. We can return it
          // directly or recurse over it again with the new token name.
          $new_token = "$base--$property_name";
          $contexts[$new_token] = $this->getContextFromProperty($property_name, $property, $contexts[$base]);
          return empty($subtoken) ? $contexts[$new_token] : $this->convertTokenToContext("$new_token:$subtoken", $contexts);
        }
      }
      // @todo improve this exception message.
      throw new ContextNotFoundException("The requested context was not found in the supplied array of contexts.");
    }
  }

  /**
   * Provides an administrative label for a tokenized relationship.
   *
   * @param string $token
   *   The token related to a context in the contexts array.
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *  An array of contexts from which to extract our token's label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The administrative label of $token.
   */
  public function getLabelByToken($token, $contexts) {
    $token = strrev($token);
    list($property_name, $base) = explode(':', $token, 2);
    $base = strrev($base);
    $property_name = strrev($property_name);
    if (isset($contexts[$base])) {
      /** @var \Drupal\Core\TypedData\ComplexDataDefinitionInterface $definition */
      $definition = $this->manager->createDataDefinition($contexts[$base]->getContextDefinition()->getDataType());
      /**
       * @var \Drupal\Core\Field\BaseFieldDefinition $property
       */
      $property = $definition->getPropertyDefinition($property_name);
      if ($property) {
        return $this->getRelatedPropertyLabel($property, $contexts[$base], $base);
      }
    }
  }

  /**
   * Extracts an array of tokens and labels of the required data type.
   *
   * This method can specify a data type to extract from contexts. A classic
   * example of this would be wanting to find all the 'entity_reference'
   * properties on an entity. This method will iterate over all supplied
   * contexts returning an array of tokens of type 'entity_reference' with a
   * corresponding label that denotes their relationship to the provided
   * array of contexts.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   The array of contexts with which we are currently dealing.
   * @param mixed string|array $data_types
   *   Data types to extract from the array of contexts.
   *
   * @return array
   *   An array of token keys and corresponding labels.
   */
  public function getTokensOfDataType($contexts, $data_types) {
    if (!is_array($data_types)) {
      $data_types = [$data_types];
    }
    $tokens = [];
    foreach ($contexts as $context_id => $context) {
      $data_definition = $this->manager->createDataDefinition($context->getContextDefinition()
        ->getDataType());
      /**
       * @var \Drupal\Core\Field\BaseFieldDefinition $property
       */
      if ($data_definition instanceof ComplexDataDefinitionInterface) {
        foreach ($data_definition->getPropertyDefinitions() as $property_name => $property) {
          if (in_array($property->getType(), $data_types)) {
            $tokens["$context_id:$property_name"] = $this->getRelatedPropertyLabel($property, $context, $context_id);
          }
        }
      }
    }
    return $tokens;
  }

  /**
   * Provides a label for a property by its relationship to a context.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition $property
   *   The property for which to generate a label.
   * @param \Drupal\Core\Plugin\Context\ContextInterface $context
   *   The context to which the property is related.
   * @param $context_id
   *   The context from the previous parameter's id in the contexts array.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   A label for this property for use in user interfaces.
   */
  protected function getRelatedPropertyLabel(BaseFieldDefinition $property, ContextInterface $context, $context_id) {
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
    $label = $property->getFieldStorageDefinition()->getLabel();
    $string = "@context_id: {$label->getUntranslatedString()}";
    $args = $label->getArguments();
    $args['@context_id'] = !empty($context->getContextDefinition()
      ->getLabel()) ? $context->getContextDefinition()
      ->getLabel() : "$context_id";
    $options = $label->getOptions();
    // @todo we need to really think about this label. It informs the UI extensively and should be as clear as possible.
    return $this->translation->translate($string, $args, $options);
  }

  /**
   * Returns the first applicable resolver for the given type definition.
   *
   * @param mixed $type
   *   Any parameter that could be passed to
   *     \Drupal\ctools\TypeResolverInterface::applies
   *     or
   *     \Drupal\ctools\TypeResolverInterface::appliesToProperty
   *   is viable for this method.
   *
   * @return \Drupal\ctools\TypeResolverInterface|null
   */
  protected function getFirstApplicableTypeResolver($type) {
    if (is_string($type)) {
      $method = 'applies';
    }
    elseif ($type instanceof ListDataDefinitionInterface) {
      $method = 'appliesToProperty';
    }
    foreach ($this->getSortedResolvers() as $resolver) {
      if ($resolver->$method($type)) {
        return $resolver;
      }
    }

    return NULL;
  }

  /**
   * Returns the priority sorted array of type resolvers.
   *
   * @return \Drupal\ctools\TypeResolverInterface[]
   *   An array of type resolver objects.
   */
  protected function getSortedResolvers() {
    if (!isset($this->sortedResolvers)) {
      krsort($this->resolvers);

      $this->sortedResolvers = [];
      foreach ($this->resolvers as $resolvers) {
        $this->sortedResolvers = array_merge($this->sortedResolvers, $resolvers);
      }
    }

    return $this->sortedResolvers;
  }

}
