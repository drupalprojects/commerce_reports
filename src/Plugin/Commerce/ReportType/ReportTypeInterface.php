<?php

namespace Drupal\commerce_reports\Plugin\Commerce\ReportType;

use Drupal\entity\BundlePlugin\BundlePluginInterface;
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
   * Builds the aggregate query.
   *
   * Report type plugins should add their field columns, aggregates, and
   * groupBy statements here.
   *
   * @param \Drupal\Core\Entity\Query\QueryAggregateInterface $query
   *   The aggregate query.
   */
  public function buildQuery(QueryAggregateInterface $query);

  /**
   * Build a report table from query results.
   *
   * @param array $results
   *   The report query results.
   *
   * @return array
   *   The render array.
   */
  public function buildReportTable(array $results);

}
