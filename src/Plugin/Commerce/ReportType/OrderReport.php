<?php

namespace Drupal\commerce_reports\Plugin\Commerce\ReportType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_reports\Annotation\CommerceReportType;
use Drupal\commerce_reports\Entity\OrderReportInterface;
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

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function generateReport(OrderReportInterface $order_report, OrderInterface $order) {
    $order_report->get('amount')->setValue($order->getTotalPrice());
  }

}
