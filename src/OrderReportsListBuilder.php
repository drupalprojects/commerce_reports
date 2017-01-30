<?php

namespace Drupal\commerce_reports;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class OrderReportsListBuilder extends EntityListBuilder {
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['amount'] = $this->t('Amount');
    $header['tax_amount'] = $this->t('Tax amount');
    $header['shipping_amount'] = $this->t('Shipping amount');
    return parent::buildHeader() + $header;
  }

  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_reports\Entity\OrderReportInterface $entity */
    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
    ] + $entity->toUrl()->toRenderArray();
    $row['amount']['data'] = [
      '#type' => 'inline_template',
      '#template' => '{{ amount|commerce_price_format }}',
      '#context' => [
        'amount' => $entity->getAmount(),
      ]
    ];
    $row['tax_amount']['data'] = [
      '#type' => 'inline_template',
      '#template' => '{{ amount|commerce_price_format }}',
      '#context' => [
        'amount' => $entity->getTaxAmount(),
      ]
    ];
    $row['shipping_amount']['data'] = [
      '#type' => 'inline_template',
      '#template' => '{{ amount|commerce_price_format }}',
      '#context' => [
        'amount' => $entity->getShippingAmount(),
      ]
    ];
    return $row + parent::buildRow($entity);
  }


}
