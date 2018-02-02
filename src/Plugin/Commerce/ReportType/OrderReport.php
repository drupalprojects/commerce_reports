<?php

namespace Drupal\commerce_reports\Plugin\Commerce\ReportType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_reports\Annotation\CommerceReportType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides the basic Order Report
 *
 * @CommerceReportType(
 *   id = "order_report",
 *   label = @Translation("Order Report"),
 *   description = @Translation("Basic order report with order id, total, and created date")
 * )
 */
class OrderReport extends ReportTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['amount'] = BundleFieldDefinition::create('commerce_price')
      ->setLabel(t('Total Amount'))
      ->setDescription(t('The total amount of the order'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['order_id'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Order'))
      ->setDescription(t('The parent order.'))
      ->setSetting('target_type', 'commerce_order')
      ->setReadOnly(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function generateReport(OrderInterface $order) {
    /** @var EntityStorageInterface $orderReportStorage */
    $orderReportStorage = \Drupal::service('entity_type.manager')
      ->getStorage('commerce_order_report');
    $order_report = $orderReportStorage->create([
      'type' => 'order_report',
      'order_id' => $order->id(),
      'amount' => $order->getTotalPrice(),
      'created' => $order->getPlacedTime(),
    ]);
    // @todo decide on how to allow others to add additional field data.
    $order_report->save();
  }
}
