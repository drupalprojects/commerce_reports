<?php

namespace Drupal\commerce_reports\Plugin\Commerce\ReportType;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_reports\Entity\OrderReportInterface;

/**
 * Interface for report types that are for orders.
 */
interface OrderReportTypeInterface extends ReportTypeInterface {

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

}
