<?php

namespace Drupal\commerce_reports\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

interface OrderReportInterface extends ContentEntityInterface {

  /**
   * Gets the order ID.
   *
   * @return int
   *   The order ID.
   */
  public function getOrderId();

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order entity.
   */
  public function getOrder();

  /**
   * Gets the order total amount.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The order total amount, or NULL.
   */
  public function getAmount();

  /**
   * Gets the order total tax amount.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The order total tax amount, or NULL.
   */
  public function getTaxAmount();

  /**
   * Gets the order total shipping amount.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The order total shipping amount, or NULL.
   */
  public function getShippingAmount();

  /**
   * Gets the order report creation timestamp.
   *
   * This is when the order transitions from a draft to a non-draft state,
   * allowing for the report to be generated because it became immutable.
   *
   * @return int
   *   Creation timestamp of the order report.
   */
  public function getCreatedTime();
}
