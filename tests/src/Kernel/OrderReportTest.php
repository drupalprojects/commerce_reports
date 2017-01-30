<?php

namespace Drupal\Tests\commerce_reports\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\commerce_reports\Entity\OrderReport;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the `commerce_order_report` entity.
 *
 * @group commerce_reports
 */
class OrderReportTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_reports',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installConfig('commerce_order');
    $this->installEntitySchema('commerce_order_report');
  }

  public function testOrderReport() {
    $order = Order::create([
      'type' => 'default',
      'state' => 'completed',
    ]);
    $order->save();
    $time = $this->container->get('commerce.time')->getCurrentTime();
    $order_report = OrderReport::create([
      'order_id' => $order->id(),
      'amount' => new Price('1.00', 'USD'),
      'tax_amount' => new Price('2.00', 'USD'),
      'shipping_amount' => new Price('3.00', 'USD'),
      'created' => $time,
    ]);
    $order_report->save();

    $this->assertEquals($order->id(), $order_report->getOrderId());
    $this->assertEquals(new Price('1.00', 'USD'), $order_report->getAmount());
    $this->assertEquals(new Price('2.00', 'USD'), $order_report->getTaxAmount());
    $this->assertEquals(new Price('3.00', 'USD'), $order_report->getShippingAmount());
    $this->assertEquals($time, $order_report->getCreatedTime());
  }

}
