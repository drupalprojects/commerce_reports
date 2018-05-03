<?php

namespace Drupal\commerce_reports\EventSubscriber;

use Drupal\commerce_reports\ReportTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber to order placed transition event.
 */
class OrderPlacedEventSubscriber implements EventSubscriberInterface {

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
   * The report type manager.
   *
   * @var \Drupal\commerce_reports\ReportTypeManager
   */
  protected $reportTypeManager;

  /**
   * Constructs a new OrderPlacedEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   * @param \Drupal\commerce_reports\ReportTypeManager $report_type_manager
   *   The order report type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StateInterface $state, ReportTypeManager $report_type_manager) {
    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
    $this->state = $state;
    $this->reportTypeManager = $report_type_manager;
  }

  /**
   * Flags the order to have a report generated.
   *
   * @todo come up with better flagging.
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
   * This creates the base order report populated with the bundle plugin ID,
   * order ID, and created timestamp from when the order was placed. Each
   * plugin then sets its values.
   *
   * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   The post response event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function generateReports(PostResponseEvent $event) {
    $order_ids = $this->state->get('commerce_order_reports', []);
    $orders = $this->orderStorage->loadMultiple($order_ids);
    $plugin_types = $this->reportTypeManager->getDefinitions();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    foreach ($orders as $order) {
      foreach ($plugin_types as $plugin_type) {
        /** @var \Drupal\commerce_reports\Plugin\Commerce\ReportType\ReportTypeInterface $instance */
        $instance = $this->reportTypeManager->createInstance($plugin_type['id'], []);
        $instance->generateReports($order);
      }
    }

    // @todo this could lose data, possibly as its global state.
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
