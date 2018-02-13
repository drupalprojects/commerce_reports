<?php

namespace Drupal\commerce_reports\Plugin\Commerce\ReportType;

use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_reports\Entity\OrderReportInterface;

/**
 * Provides the basic Order Report.
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
    $fields['mail'] = BundleFieldDefinition::create('email')
      ->setLabel(t('Contact email'))
      ->setDescription(t('The email address associated with the order.'))
      ->setCardinality(1)
      ->setSetting('max_length', 255)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['billing_address'] = BundleFieldDefinition::create('address')
      ->setLabel(t('Address'))
      ->setDescription(t('The store address.'))
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
    $order_report->get('mail')->setValue($order->getEmail());

    $billing_profile = $order->getBillingProfile();
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
    $address = $billing_profile->get('address')->first();
    $order_report->get('billing_address')->setValue($address->toArray());
  }

  /**
   * {@inheritdoc}
   */
  public function buildQuery(QueryAggregateInterface $query) {
    $query->aggregate('mail', 'COUNT');
    $query->aggregate('amount.number', 'SUM');
    $query->aggregate('amount.number', 'AVG');
    $query->groupBy('amount.currency_code');
  }

}
