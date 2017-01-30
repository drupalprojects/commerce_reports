<?php

namespace Drupal\commerce_reports\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the order report entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_order_report",
 *   label = @Translation("Order report"),
 *   label_singular = @Translation("order report"),
 *   label_plural = @Translation("order reports"),
 *   label_count = @PluralTranslation(
 *     singular = "@count order report",
 *     plural = "@count order reports",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\commerce\EntityAccessControlHandler",
 *     "permission_provider" = "Drupal\commerce\EntityPermissionProvider",
 *     "list_builder" = "Drupal\commerce_reports\OrderReportsListBuilder",
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_order_report",
 *   admin_permission = "administer commerce_order_report",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "report_id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/commerce/reports/orders",
 *     "canonical" = "/admin/commerce/reports/orders/{commerce_order_report}",
 *   },
 * )
 */
class OrderReport extends ContentEntityBase implements OrderReportInterface {

  public function label() {
    if (!$this->isNew()) {
      return t('Order report for @order_number', ['@order_number' => $this->getOrder()->getOrderNumber()]);
    }
    return parent::label();
  }

  /**
   * {@inheritdoc}
   */
  public function getOrder() {
    return $this->get('order_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderId() {
    return $this->get('order_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount() {
    if (!$this->get('amount')->isEmpty()) {
      return $this->get('amount')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTaxAmount() {
    if (!$this->get('tax_amount')->isEmpty()) {
      return $this->get('tax_amount')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getShippingAmount() {
    if (!$this->get('shipping_amount')->isEmpty()) {
      return $this->get('shipping_amount')->first()->toPrice();
    }
  }


  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order'))
      ->setDescription(t('The parent order.'))
      ->setSetting('target_type', 'commerce_order')
      ->setReadOnly(TRUE);
    $fields['amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Amount'))
      ->setDescription(t('The order total amount.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['tax_amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Tax amount'))
      ->setDescription(t('The tax amount.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['shipping_amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Shipping amount'))
      ->setDescription(t('The shipping amount.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the order report was created.'))
      ->setReadOnly(TRUE);

    return $fields;
  }

}
