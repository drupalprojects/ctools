<?php

/**
 * @file
 * Contains \Drupal\Tests\ctools\Unit\BlockDisplayVariantTest.
 */

namespace Drupal\Tests\ctools\Unit;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\ctools\Plugin\BlockPluginCollection;
use Drupal\ctools\Plugin\DisplayVariant\BlockDisplayVariant;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the block display variant plugin.
 *
 * @coversDefaultClass \Drupal\ctools\Plugin\DisplayVariant\BlockDisplayVariant
 *
 * @group CTools
 */
class BlockDisplayVariantTest extends UnitTestCase {

  /**
   * Tests the access() method.
   *
   * @covers ::access
   */
  public function testAccess() {
    $display_variant = $this->getMockBuilder(TestBlockDisplayVariant::class)
      ->disableOriginalConstructor()
      ->setMethods(['determineSelectionAccess'])
      ->getMock();
    $display_variant->expects($this->once())
      ->method('determineSelectionAccess')
      ->willReturn(FALSE);
    $this->assertSame(FALSE, $display_variant->access());

    $display_variant = $this->getMockBuilder(TestBlockDisplayVariant::class)
      ->disableOriginalConstructor()
      ->setMethods(['determineSelectionAccess'])
      ->getMock();
    $display_variant->expects($this->once())
      ->method('determineSelectionAccess')
      ->willReturn(TRUE);
    $this->assertSame(TRUE, $display_variant->access());
  }

  /**
   * Tests the submitConfigurationForm() method.
   *
   * @covers ::submitConfigurationForm
   *
   * @dataProvider providerTestSubmitConfigurationForm
   */
  public function testSubmitConfigurationForm($values) {
    $account = $this->prophesize(AccountInterface::class);
    $context_handler = $this->prophesize(ContextHandlerInterface::class);
    $uuid_generator = $this->prophesize(UuidInterface::class);
    $token = $this->prophesize(Token::class);
    $block_manager = $this->prophesize(BlockManagerInterface::class);
    $condition_manager = $this->prophesize(ConditionManager::class);

    $display_variant = new TestBlockDisplayVariant([], '', [], $context_handler->reveal(), $account->reveal(), $uuid_generator->reveal(), $token->reveal(), $block_manager->reveal(), $condition_manager->reveal());

    $form = [];
    $form_state = (new FormState())->setValues($values);
    $display_variant->submitConfigurationForm($form, $form_state);
    $this->assertSame($values['label'], $display_variant->label());
  }

  /**
   * Provides data for testSubmitConfigurationForm().
   */
  public function providerTestSubmitConfigurationForm() {
    $data = [];
    $data[] = [
      [
        'label' => 'test_label1',
      ],
    ];
    $data[] = [
      [
        'label' => 'test_label2',
        'blocks' => ['foo1' => []],
      ],
    ];
    $data[] = [
      [
        'label' => 'test_label3',
        'blocks' => ['foo1' => [], 'foo2' => []],
      ],
    ];
    return $data;
  }

}

class TestBlockDisplayVariant extends BlockDisplayVariant {

  /**
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * Constructs a new BlockDisplayVariant.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   The UUID generator.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextHandlerInterface $context_handler, AccountInterface $account, UuidInterface $uuid_generator, Token $token, BlockManagerInterface $block_manager, ConditionManager $condition_manager) {
    $this->blockManager = $block_manager;
    $this->conditionManager = $condition_manager;

    parent::__construct($configuration, $plugin_id, $plugin_definition, $context_handler, $account, $uuid_generator, $token);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBlockCollection() {
    if (!$this->blockPluginCollection) {
      $this->blockPluginCollection = new BlockPluginCollection($this->blockManager, $this->getBlockConfig());
    }
    return $this->blockPluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionConditions() {
    if (!$this->selectionConditionCollection) {
      $this->selectionConditionCollection = new ConditionPluginCollection($this->conditionManager, $this->getSelectionConfiguration());
    }
    return $this->selectionConditionCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }

  public function getRegionNames() {
    return [
      'top' => 'Top',
      'bottom' => 'Bottom',
    ];
  }

}
