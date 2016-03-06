<?php
/**
 * @file
 * Contains \Drupal\Tests\ctools\Kernel\TypedDataRelationshipPluginTest.
 */

namespace Drupal\Tests\ctools\Kernel;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceInterface;
use Drupal\Core\TypedData\ListInterface;

/**
 * @coversDefaultClass \Drupal\ctools\Plugin\Relationship\TypedDataRelationship
 * @group Plugin
 */
class TypedDataRelationshipPluginTest extends RelationshipsTestBase {

  /**
   * @covers ::getRelationshipValue
   */
  public function testRelationshipValue() {
    /** @var \Drupal\ctools\Plugin\RelationshipInterface $nid_plugin */
    $nid_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:nid');
    $nid_plugin->setContextValue('base', $this->entities['node1']);
    $value = $nid_plugin->getRelationshipValue();
    $this->assertSame($this->entities['node1']->id(), $value);

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $uuid_plugin */
    $uuid_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:uuid');
    $uuid_plugin->setContextValue('base', $this->entities['node1']);
    $value = $uuid_plugin->getRelationshipValue();
    $this->assertSame($this->entities['node1']->uuid(), $value);

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $title_plugin */
    $title_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:title');
    $title_plugin->setContextValue('base', $this->entities['node1']);
    $value = $title_plugin->getRelationshipValue();
    $this->assertSame($this->entities['node1']->label(), $value);

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $body_plugin */
    $body_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:body');
    $body_plugin->setContextValue('base', $this->entities['node1']);
    $value = $body_plugin->getRelationshipValue();
    $this->assertSame($this->entities['node1']->get('body')->get(0)->get('value')->getValue(), $value);

    $nid_plugin->setContextValue('base', $this->entities['node2']);
    $value = $nid_plugin->getRelationshipValue();
    $this->assertSame($this->entities['node2']->id(), $value);

    $nid_plugin->setContextValue('base', $this->entities['node3']);
    $value = $nid_plugin->getRelationshipValue();
    $this->assertSame($this->entities['node3']->id(), $value);

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $uid_plugin */
    $uid_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:uid');
    $uid_plugin->setContextValue('base', $this->entities['node3']);
    $value = $uid_plugin->getRelationshipValue();
    $this->assertSame($this->entities['node3']->get('uid')->first()->get('target_id')->getValue(), $value);

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $mail_plugin */
    $mail_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:user:mail');
    $mail_plugin->setContextValue('base', $this->entities['user']);
    $value = $mail_plugin->getRelationshipValue();
    $this->assertSame($this->entities['user']->getEmail(), $value);
  }

  /**
   * @covers ::getName
   */
  public function testRelationshipName() {
    /** @var \Drupal\ctools\Plugin\RelationshipInterface $nid_plugin */
    $nid_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:nid');
    $this->assertSame('nid', $nid_plugin->getName());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $uuid_plugin */
    $uuid_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:uuid');
    $this->assertSame('uuid', $uuid_plugin->getName());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $title_plugin */
    $title_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:title');
    $this->assertSame('title', $title_plugin->getName());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $body_plugin */
    $body_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:body');
    $this->assertSame('body', $body_plugin->getName());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $uid_plugin */
    $uid_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:uid');
    $this->assertSame('uid', $uid_plugin->getName());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $mail_plugin */
    $mail_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:user:mail');
    $this->assertSame('mail', $mail_plugin->getName());
  }

  protected function getContext($property_name, ContextInterface $context) {
    $data = $context->getContextValue();
    $property = $data->getFieldDefinition($property_name);
    $data_type = $data->get($property_name)->first()->getDataDefinition()->getDataType();
    $label = new TranslatableMarkup('@property from @base', ['@property' => $property->getLabel(), '@base' => $this->getEntityTypeManager()->getDefinition($data->getEntityTypeId())->getLabel()]);
    $definition = new ContextDefinition($data_type, $label);

    $data = $data->get($property_name);
    if ($data instanceof ListInterface) {
      $data = $data->first();
    }
    if ($data instanceof DataReferenceInterface) {
      $data = $data->getTarget();
    }
    $value = $data->getValue();
    $definition->setDefaultValue($value);
    return new Context($definition, $value);
  }

  /**
   * @covers ::getRelationship
   */
  public function testRelationship() {
    /** @var \Drupal\ctools\Plugin\RelationshipInterface $nid_plugin */
    $nid_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:nid');
    $nid_plugin->setContextValue('base', $this->entities['node1']);
    $context = new Context(new ContextDefinition('entity:node'), $this->entities['node1']);
    $expected = $this->getContext('nid', $context);
    $relationship = $nid_plugin->getRelationship();
    $this->assertTrue($relationship instanceof ContextInterface);
    $this->assertTrue($expected->getContextDefinition()->getDataType() === $relationship->getContextDefinition()->getDataType());
    $this->assertTrue($relationship->hasContextValue());
    $this->assertTrue($relationship->getContextValue()->get('value')->getValue() == $this->entities['node1']->id());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $uuid_plugin */
    $uuid_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:uuid');
    $uuid_plugin->setContextValue('base', $this->entities['node1']);
    $context = new Context(new ContextDefinition('entity:node'), $this->entities['node1']);
    $expected = $this->getContext('uuid', $context);
    $relationship = $uuid_plugin->getRelationship();
    $this->assertTrue($relationship instanceof ContextInterface);
    $this->assertTrue($expected->getContextDefinition()->getDataType() === $relationship->getContextDefinition()->getDataType());
    $this->assertTrue($relationship->hasContextValue());
    $this->assertTrue($relationship->getContextValue()->get('value')->getValue() == $this->entities['node1']->uuid());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $title_plugin */
    $title_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:title');
    $title_plugin->setContextValue('base', $this->entities['node1']);
    $context = new Context(new ContextDefinition('entity:node'), $this->entities['node1']);
    $expected = $this->getContext('title', $context);
    $relationship = $title_plugin->getRelationship();
    $this->assertTrue($relationship instanceof ContextInterface);
    $this->assertTrue($expected->getContextDefinition()->getDataType() === $relationship->getContextDefinition()->getDataType());
    $this->assertTrue($relationship->hasContextValue());
    $this->assertTrue($relationship->getContextValue()->get('value')->getValue() == $this->entities['node1']->label());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $body_plugin */
    $body_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:body');
    $body_plugin->setContextValue('base', $this->entities['node1']);
    $context = new Context(new ContextDefinition('entity:node'), $this->entities['node1']);
    $expected = $this->getContext('body', $context);
    $relationship = $body_plugin->getRelationship();
    $this->assertTrue($relationship instanceof ContextInterface);
    $this->assertTrue($expected->getContextDefinition()->getDataType() === $relationship->getContextDefinition()->getDataType());
    $this->assertTrue($relationship->hasContextValue());
    $this->assertTrue($relationship->getContextValue()->get('value')->getValue() == $this->entities['node1']->get('body')->first()->get('value')->getValue());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $uid_plugin */
    $uid_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:uid');
    $uid_plugin->setContextValue('base', $this->entities['node3']);
    $context = new Context(new ContextDefinition('entity:node'), $this->entities['node3']);
    $expected = $this->getContext('uid', $context);
    $relationship = $uid_plugin->getRelationship();
    $this->assertTrue($relationship instanceof ContextInterface);
    $this->assertTrue($expected->getContextDefinition()->getDataType() === $relationship->getContextDefinition()->getDataType());
    $this->assertTrue($relationship->hasContextValue());
    $this->assertTrue($relationship->getContextValue()->get('target_id')->getValue() == $this->entities['node3']->getOwnerId());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $mail_plugin */
    $mail_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:user:mail');
    $mail_plugin->setContextValue('base', $this->entities['user']);
    $context = new Context(new ContextDefinition('entity:node'), $this->entities['user']);
    $expected = $this->getContext('mail', $context);
    $relationship = $mail_plugin->getRelationship();
    $this->assertTrue($relationship instanceof ContextInterface);
    $this->assertTrue($expected->getContextDefinition()->getDataType() === $relationship->getContextDefinition()->getDataType());
    $this->assertTrue($relationship->hasContextValue());
    $this->assertTrue($relationship->getContextValue()->get('value')->getValue() == $this->entities['user']->getEmail());
  }

}
