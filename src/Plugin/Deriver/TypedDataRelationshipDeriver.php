<?php
/**
 * @file
 * Contains \Drupal\ctools\Plugin\Deriver\TypedDataRelationshipDeriver.
 */

namespace Drupal\ctools\Plugin\Deriver;


use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\field\FieldConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TypedDataRelationshipDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(TypedDataManagerInterface $typed_data_manager, TranslationInterface $string_translation) {
    $this->typedDataManager = $typed_data_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('typed_data_manager'),
      $container->get('string_translation')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->typedDataManager->getDefinitions() as $data_type => $definition) {
     if (is_subclass_of($definition['class'], ComplexDataInterface::class, TRUE)) {
        /** @var \Drupal\Core\TypedData\ComplexDataDefinitionInterface $base_definition */
        $base_definition = $this->typedDataManager->createDataDefinition($data_type);
        foreach ($base_definition->getPropertyDefinitions() as $property_name => $property_definition) {
          $bundle_info = $base_definition->getConstraint('Bundle');
          if ($bundle_info && array_filter($bundle_info) && $base_definition->getConstraint('EntityType')) {
            $base_data_type =  'entity:' . $base_definition->getConstraint('EntityType');
          }
          else {
            $base_data_type = $data_type;
          }
          if (!isset($this->derivatives[$base_data_type . ':' . $property_name])) {
            $derivative = $base_plugin_definition;

            $derivative['label'] = $this->t('@property from @base', [
              '@property' => $property_definition->getLabel(),
              '@base' => $definition['label'],
            ]);
            $derivative['data_type'] = $this->getDataType($property_definition);
            $derivative['property_name'] = $property_name;
            $context_definition = new ContextDefinition($base_data_type, $this->typedDataManager->createDataDefinition($base_data_type));
            if ($base_definition->getConstraint('Bundle')) {
              $context_definition->addConstraint('Bundle', $base_definition->getConstraint('Bundle'));
            }
            $derivative['context'] = [
              'base' => $context_definition,
            ];
            $derivative['property_name'] = $property_name;

            $this->derivatives[$base_data_type . ':' . $property_name] = $derivative;
          }
          // Individual fields can be on multiple bundles.
          elseif ($property_definition instanceof FieldConfigInterface) {
            // We should only end up in here on entity bundles.
            $derivative = $this->derivatives[$base_data_type . ':' . $property_name];
            // Update label
            /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
            $label = $derivative['label'];
            list(,, $argument_name) = explode(':', $data_type);
            $arguments = $label->getArguments();
            $arguments['@'. $argument_name] = $definition['label'];
            $string_args = $arguments;
            array_shift($string_args);
            $last = array_slice($string_args, -1);
            // The slice doesn't remove, so do that now.
            array_pop($string_args);
            $string = count($string_args) >= 2 ? '@property from '. implode(', ', array_keys($string_args)) .' and '. array_keys($last)[0] : '@property from @base and '. array_keys($last)[0];
            $this->derivatives[$base_data_type . ':' . $property_name]['label'] = $this->t($string, $arguments);
            if ($base_definition->getConstraint('Bundle')) {
              // Add bundle constraints
              $context_definition = $derivative['context']['base'];
              $bundles = $context_definition->getConstraint('Bundle') ?: [];
              $bundles = array_merge($bundles, $base_definition->getConstraint('Bundle'));
              $context_definition->addConstraint('Bundle', $bundles);
            }
          }
        }
      }
    }

    return $this->derivatives;
  }

  protected function getDataType($property_definition) {
    if ($property_definition instanceof DataReferenceDefinitionInterface) {
      return $property_definition->getTargetDefinition()->getDataType();
    }
    if ($property_definition instanceof ListDataDefinitionInterface) {
      return $property_definition->getItemDefinition()->getDataType();
    }
    return $property_definition->getDataType();
  }

}
