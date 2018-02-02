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

}
