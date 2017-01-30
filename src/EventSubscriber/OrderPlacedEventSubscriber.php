<?php

namespace Drupal\commerce_reports\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class OrderPlacedEventSubscriber implements EventSubscriberInterface {

  /**
   * The order report storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderReportStorage;

  /**
   * The order storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderStorage;

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new OrderPlacedEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StateInterface $state) {
    $this->orderReportStorage = $entity_type_manager->getStorage('commerce_order_report');
    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
    $this->state = $state;
  }

  /**
   * Flags the order to have a report generated.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function flagOrder(WorkflowTransitionEvent $event) {
    $order = $event->getEntity();

    $existing = $this->state->get('commerce_order_reports', []);
    $existing[] = $order->id();
    $this->state->set('commerce_order_reports', $existing);
  }

  /**
   * Generates order reports once output flushed.
   *
   * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   The post response event.
   */
  public function generateReports(PostResponseEvent $event) {
    $order_ids = $this->state->get('commerce_order_reports', []);
    $orders = $this->orderStorage->loadMultiple($order_ids);

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    foreach ($orders as $order) {
      $order_report = $this->orderReportStorage->create([
        'order_id' => $order->id(),
        'amount' => $order->getTotalPrice(),
        'created' => $order->getPlacedTime()
      ]);
      // @todo decide on how to allow others to add additional field data.
      $order_report->save();
    }

    $this->state->set('commerce_order_reports', []);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.pre_transition' => 'flagOrder',
      KernelEvents::TERMINATE => 'generateReports',
    ];
    return $events;
  }

}
