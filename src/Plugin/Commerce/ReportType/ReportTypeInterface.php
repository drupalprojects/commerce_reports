<?php

namespace Drupal\commerce_reports\Plugin\Commerce\ReportType;

use Drupal\commerce\BundlePluginInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_reports\Entity\OrderReportInterface;
use Drupal\Core\Entity\Query\QueryAggregateInterface;

/**
 * Defines the interface for order report types.
 */
interface ReportTypeInterface extends BundlePluginInterface {

  /**
   * Gets the order report type label.
   *
   * @return string
   *   The order report type label.
   */
  public function getLabel();

  /**
   * Gets the order report type description.
   *
   * @return string
   *   The order report type description.
   */
  public function getDescription();

  /**
   * Generates an order report.
   *
   * The order report entity should not be saved during this method.
   *
   * @param \Drupal\commerce_reports\Entity\OrderReportInterface $order_report
   *   The order report.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function generateReport(OrderReportInterface $order_report, OrderInterface $order);

  /**
   * Builds the aggregate query.
   *
   * Report type plugins should add their field columns, aggregates, and
   * groupBy statements here.
   *
   * @param \Drupal\Core\Entity\Query\QueryAggregateInterface $query
   *   The aggregate query.
   */
  public function buildQuery(QueryAggregateInterface $query);

}
