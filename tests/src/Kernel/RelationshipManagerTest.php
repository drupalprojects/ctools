<?php
/**
 * @file
 * Contains \Drupal\Tests\ctools\KernelTests\RelationshipPluginTest.
 */

namespace Drupal\Tests\ctools\Kernel;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\ctools\Testing\EntityCreationTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\ctools\Plugin\RelationshipManagerInterface
 * @group Plugin
 */
class RelationshipManagerTest extends KernelTestBase {
  use EntityCreationTrait;

  /**
   * @var \Drupal\ctools\Plugin\RelationshipManagerInterface
   */
  protected $relationshipManager;

  /**
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $entities = [];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'system',
    'node',
    'field',
    'text',
    'filter',
    'ctools'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node_type');
    $this->installEntitySchema('node');
    $this->installConfig('node');
    $page = $this->createEntity('node_type', [
      'type' => 'page',
      'name' => 'Page'
    ]);
    node_add_body_field($page);
    $article = $this->createEntity('node_type', [
      'type' => 'article',
      'name' => 'Article'
    ]);
    // Not adding the body field the articles so that we can perform a test.
    $foo = $this->createEntity('node_type', [
      'type' => 'foo',
      'name' => 'Foo'
    ]);
    node_add_body_field($foo);
    $this->relationshipManager = $this->container->get('plugin.manager.ctools.relationship');

    $user = $this->createEntity('user', [
      'name' => 'test_user',
      'password' => 'password',
      'mail' => 'mail@test.com',
      'status' => 1,
    ]);
    $node1 = $this->createEntity('node', [
      'title' => 'Node 1',
      'type' => 'page',
      'uid' => $user->id(),
      'body' => 'This is a test',
    ]);
    $node2 = $this->createEntity('node', [
      'title' => 'Node 2',
      'type' => 'article',
      'uid' => $user->id()
    ]);
    $node3 = $this->createEntity('node', [
      'title' => 'Node 3',
      'type' => 'foo',
      'uid' => $user->id()
    ]);

    $this->entities = [
      'user' => $user,
      'node1' => $node1,
      'node2' => $node2,
      'node3' => $node3,
    ];
  }

  /**
   * @covers ::getDefinitions
   */
  public function testRelationshipConstraints() {
    $definitions = $this->relationshipManager->getDefinitions();
    $expected = [
      'Bundle' => [
        0 => "page",
        1 => "foo"
      ]
    ];
    $this->assertSame($expected, $definitions['typed_data_relationship:entity:node:body']['context']['base']->getConstraints());
  }

  /**
   * @covers ::getDefinitionsForContexts
   */
  public function testRelationshipPluginAvailability() {
    $context_definition = new ContextDefinition('entity:node');
    $contexts = [
      'node' => new Context($context_definition, $this->entities['node1']),
    ];
    $definitions = $this->relationshipManager->getDefinitionsForContexts($contexts);
    $this->assertTrue(isset($definitions['typed_data_relationship:entity:node:body']));

    $context_definition = new ContextDefinition('entity:node');
    $contexts = [
      'node' => new Context($context_definition, $this->entities['node2']),
    ];
    $definitions = $this->relationshipManager->getDefinitionsForContexts($contexts);
    $this->assertFalse(isset($definitions['typed_data_relationship:entity:node:body']));

    $context_definition = new ContextDefinition('entity:node');
    $contexts = [
      'node' => new Context($context_definition, $this->entities['node3']),
    ];
    $definitions = $this->relationshipManager->getDefinitionsForContexts($contexts);
    $this->assertTrue(isset($definitions['typed_data_relationship:entity:node:body']));
  }

}
