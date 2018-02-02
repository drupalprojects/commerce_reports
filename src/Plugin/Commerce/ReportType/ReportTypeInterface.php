<?php

namespace Drupal\commerce_reports\Plugin\Commerce\ReportType;

use Drupal\commerce\BundlePluginInterface;
use Drupal\commerce_order\Entity\OrderInterface;

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
   * Gets the order report type description
   *
   * @return string
   *   The order report type description.
   */
  public function getDescription();


  /**
   * Creates an order report
   *
   * @param OrderInterface $order
   *   The order.
   */
  public function generateReport(OrderInterface $order);

}
