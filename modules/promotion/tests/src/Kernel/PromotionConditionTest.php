<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_order\Entity\LineItem;
use Drupal\commerce_order\Entity\LineItemType;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests promotion conditions.
 *
 * @group commerce
 */
class PromotionConditionTest extends KernelTestBase {

  use StoreCreationTrait;

  /**
   * The default store.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

  /**
   * The condition manager.
   *
   * @var \Drupal\commerce_promotion\PromotionConditionManager
   */
  protected $conditionManager;

  /**
   * The test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'field', 'options', 'user', 'views', 'profile',
    'text', 'entity', 'commerce', 'commerce_price', 'address', 'commerce_order',
    'commerce_store', 'commerce_product', 'inline_entity_form', 'commerce_promotion',
    'state_machine', 'datetime',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_type');
    $this->installEntitySchema('commerce_line_item');
    $this->installEntitySchema('commerce_promotion');
    $this->installConfig([
      'profile',
      'commerce_order',
      'commerce_store',
      'commerce_promotion',
    ]);
    $this->store = $this->createStore(NULL, NULL, 'default', TRUE);

    // A line item type that doesn't need a purchasable entity, for simplicity.
    LineItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    $this->order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'store_id' => $this->store,
      'line_items' => [],
    ]);
  }

  /**
   * Tests the order amount condition.
   */
  public function testOrderTotal() {
    // Use addLineItem so the total is calculated.
    $line_item = LineItem::create([
      'type' => 'test',
      'quantity' => 2,
      'unit_price' => [
        'amount' => '20.00',
        'currency_code' => 'USD',
      ],
    ]);
    $line_item->save();
    $this->order->addLineItem($line_item);

    // Starts now, enabled. No end time.
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'commerce_promotion_order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'commerce_promotion_order_total_price',
          'target_plugin_configuration' => [
            'amount' => new Price('20.00', 'USD'),
          ],
        ],
      ],
    ]);
    $promotion->save();

    $result = $promotion->applies($this->order);

    $this->assertTrue($result);

    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'commerce_promotion_order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'commerce_promotion_order_total_price',
          'target_plugin_configuration' => [
            'amount' => new Price('50.00', 'USD'),
          ],
        ],
      ],
    ]);
    $promotion->save();

    $result = $promotion->applies($this->order);

    $this->assertFalse($result);
  }

}
