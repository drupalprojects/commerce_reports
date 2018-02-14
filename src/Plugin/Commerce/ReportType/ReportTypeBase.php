<?php

namespace Drupal\commerce_reports\Plugin\Commerce\ReportType;

use Drupal\Component\Plugin\PluginBase;

/**
 * Provides the base order report type class.
 */
abstract class ReportTypeBase extends PluginBase implements ReportTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildReportTable(array $results) {
    $build = [
      '#type' => 'table',
      '#header' => $this->doBuildReportTableHeaders(),
      '#rows' => [],
      '#empty' => t('No reports yet'),
    ];
    foreach ($results as $result) {
      $row = $this->doBuildReportTableRow($result);
      $build['#rows'][] = $row;
    }

    return $build;
  }

  /**
   * Builds the report table headers.
   *
   * @return array
   *   The report table headers.
   */
  abstract protected function doBuildReportTableHeaders();

  /**
   * Build the report table row.
   *
   * @param array $result
   *   The result row.
   *
   * @return array
   *   The table row data.
   */
  abstract protected function doBuildReportTableRow(array $result);

}
