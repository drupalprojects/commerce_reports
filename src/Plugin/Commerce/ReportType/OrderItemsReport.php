<?php

namespace Drupal\commerce_reports\Plugin\Commerce\ReportType;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce_reports\Entity\OrderReportInterface;

/**
 * Provides the Order Items Report.
 *
 * @CommerceReportType(
 *   id = "order_items_report",
 *   label = @Translation("Order Items Report"),
 *   description = @Translation("Order items.")
 * )
 */
class OrderItemsReport extends ReportTypeBase implements OrderItemReportTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['order_item_id'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Order'))
      ->setDescription(t('The parent order.'))
      ->setSetting('target_type', 'commerce_order_item')
      ->setRequired(TRUE);
    $fields['title'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The purchased item title.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 512,
      ]);
    $fields['quantity'] = BundleFieldDefinition::create('decimal')
      ->setLabel(t('Quantity'))
      ->setDescription(t('The number of purchased units.'))
      ->setRequired(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setSetting('min', 0)
      ->setDisplayConfigurable('view', TRUE);
    $fields['unit_price'] = BundleFieldDefinition::create('commerce_price')
      ->setLabel(t('Unit price'))
      ->setDescription(t('The unit price for the purchased item'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['total_price'] = BundleFieldDefinition::create('commerce_price')
      ->setLabel(t('Total price'))
      ->setDescription(t('The total price for the purchased item'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['adjusted_unit_price'] = BundleFieldDefinition::create('commerce_price')
      ->setLabel(t('Adjusted unit price'))
      ->setDescription(t('The adjusted unit price for the purchased item'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['adjusted_total_price'] = BundleFieldDefinition::create('commerce_price')
      ->setLabel(t('Adjusted total price'))
      ->setDescription(t('The adjusted total price for the purchased item'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function doBuildReportTableHeaders() {
    return [
      'formatted_date' => t('Date'),
      'order_id_count' => t('# Orders'),
      'quantity_sum' => t('# Sold'),
      'adjusted_unit_price_sum' => t('Total revenue'),
      'adjusted_total_price_sum' => t('Average revenue'),
      'unit_price_currency_code' => t('Currency'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function doBuildReportTableRow(array $result) {
    $currency_code = $result['unit_price_currency_code'];
    $row = [
      $result['formatted_date'],
      $result['order_id_count'],
      $result['quantity_sum'],
      [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{{price|commerce_price_format}}',
          '#context' => [
            'price' => new Price($result['adjusted_unit_price_sum'], $currency_code),
          ],
        ],
      ],
      [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{{price|commerce_price_format}}',
          '#context' => [
            'price' => new Price($result['adjusted_total_price_sum'], $currency_code),
          ],
        ],
      ],
      $currency_code,
    ];
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function generateReport(OrderReportInterface $order_report, OrderItemInterface $order_item) {
    $order_report->get('order_item_id')->setValue($order_item->id());
    $order_report->get('title')->setValue($order_item->label());
    $order_report->get('quantity')->setValue($order_item->getQuantity());
    $order_report->get('unit_price')->setValue($order_item->getUnitPrice());
    $order_report->get('total_price')->setValue($order_item->getTotalPrice());
    $order_report->get('adjusted_unit_price')->setValue($order_item->getAdjustedUnitPrice());
    $order_report->get('adjusted_total_price')->setValue($order_item->getAdjustedTotalPrice());
  }

  /**
   * {@inheritdoc}
   */
  public function buildQuery(QueryAggregateInterface $query) {
    $query->aggregate('title', 'COUNT');
    $query->aggregate('quantity', 'SUM');
    $query->aggregate('unit_price.number', 'SUM');
    $query->aggregate('total_price.number', 'SUM');
    $query->aggregate('adjusted_unit_price.number', 'SUM');
    $query->aggregate('adjusted_total_price.number', 'SUM');
    $query->groupBy('title');
    $query->groupBy('unit_price.currency_code');
  }

}
