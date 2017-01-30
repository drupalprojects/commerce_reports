<?php

namespace Drupal\Tests\commerce_reports\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_reports\Entity\OrderReport;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Test order report generation.
 *
 * @group commerce_reports
 */
class OrderReportGenerationTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'path',
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_product',
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
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_product');
    $this->installEntitySchema('commerce_order_report');
  }

  /**
   * Tests that order reports are generated when an order is placed.
   */
  public function testOrderReportGeneration() {
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
    ]);
    $variation->save();
    $product = Product::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$variation],
    ]);
    $product->save();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'uid' => 0,
      'cart' => TRUE,
    ]);
    $order_item = $this->container->get('entity_type.manager')->getStorage('commerce_order_item')->createFromPurchasableEntity($variation, [
      'quantity' => 1,
    ]);
    $order_item->save();
    $order->setRefreshState(Order::REFRESH_SKIP);
    $order->addItem($order_item);
    $order->save();
    $workflow = $order->getState()->getWorkflow();
    $order->getState()->applyTransition($workflow->getTransition('place'));
    $order->setRefreshState(Order::REFRESH_SKIP);
    $order->save();

    $this->assertEquals([$order->id()], $this->container->get('state')->get('commerce_order_reports'));
    $this->container->get('event_dispatcher')->dispatch(KernelEvents::TERMINATE, new PostResponseEvent($this->container->get('kernel'), new Request(), new Response()));
    $this->assertEquals([], $this->container->get('state')->get('commerce_order_reports'));
    /** @var \Drupal\commerce_reports\Entity\OrderReport $order_report */
    $order_report = OrderReport::load(1);
    $this->assertEquals($order_report->getOrderId(), $order->id());
    $this->assertEquals($order_report->getAmount(), $order->getTotalPrice());
  }

}
