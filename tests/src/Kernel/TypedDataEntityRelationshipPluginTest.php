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
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\ctools\Plugin\Relationship\TypedDataEntityRelationship
 * @group Plugin
 */
class TypedDataEntityRelationshipPluginTest extends RelationshipsTestBase {

  /**
   * @covers ::getRelationshipValue
   */
  public function testRelationshipValue() {
    /** @var \Drupal\ctools\Plugin\RelationshipInterface $nid_plugin */
    $nid_plugin = $this->relationshipManager->createInstance('typed_data_entity_relationship:entity:node:type');
    $nid_plugin->setContextValue('base', $this->entities['node1']);
    $value = $nid_plugin->getRelationshipValue();
    $this->assertSame($this->entities['node1']->get('type')->entity, $value);

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $uid_plugin */
    $uid_plugin = $this->relationshipManager->createInstance('typed_data_entity_relationship:entity:node:uid');
    $uid_plugin->setContextValue('base', $this->entities['node3']);
    $value = $uid_plugin->getRelationshipValue();
    $this->assertSame($this->entities['node3']->get('uid')->first()->entity, $value);
  }

  /**
   * @covers ::getName
   */
  public function testRelationshipName() {
    /** @var \Drupal\ctools\Plugin\RelationshipInterface $nid_plugin */
    $type_plugin = $this->relationshipManager->createInstance('typed_data_entity_relationship:entity:node:type');
    $this->assertSame('type', $type_plugin->getName());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $uuid_plugin */
    $uid_plugin = $this->relationshipManager->createInstance('typed_data_entity_relationship:entity:node:uid');
    $this->assertSame('uid', $uid_plugin->getName());
  }

  /**
   * @covers ::getRelationship
   */
  public function testRelationship() {
    /** @var \Drupal\ctools\Plugin\RelationshipInterface $nid_plugin */
    $nid_plugin = $this->relationshipManager->createInstance('typed_data_entity_relationship:entity:node:type');
    $nid_plugin->setContextValue('base', $this->entities['node1']);
    $value = $nid_plugin->getRelationship();
    $this->assertTrue($value->getContextValue()->entity instanceof NodeType);

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $uid_plugin */
    $uid_plugin = $this->relationshipManager->createInstance('typed_data_entity_relationship:entity:node:uid');
    $uid_plugin->setContextValue('base', $this->entities['node3']);
    $value = $uid_plugin->getRelationship();
    $this->assertTrue($value->getContextValue()->entity instanceof User);
  }

}
