<?php

function commerce_reports_tax_form($form, &$form_state, $report, $op = 'edit') {
  if ($op == 'clone') {
    $report->title .= ' (cloned)';
  }
  
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Administrative title'),
    '#description' => t('The administrative title that will be used in the interface.'),
    '#default_value' => $report->title,
  );
   
  $form['detailled'] = array(
    '#type' => 'radios',
    '#title' => t('Type'),
    '#description' => t('Wheter or not tax information about individual orders should be generated.'),
    '#options' => array(
      '0' => 'Standard report',
      '1' => 'Extended report (includes information about orders)',
    ),
  );
  
  $form['timeframe'] = array(
    '#type' => 'fieldset',
    '#title' => t('Timeframe'),
    '#decription' => t('Which timeframe should be used in the report.'),
    '#collapsible' => TRUE,
  );
  
  $form['timeframe']['start'] = array(
    '#type' => 'date',
    '#title' => t('Start date'),
    '#default_value' => array(
      'day' => format_date($report->start, 'custom', 'j'),
      'month' => format_date($report->start, 'custom', 'n'),
      'year' => format_date($report->start, 'custom', 'Y'),
    ),
  );
  
  $form['timeframe']['end'] = array(
    '#type' => 'date',
    '#title' => t('End date'),
    '#default_value' => array(
      'day' => format_date($report->end, 'custom', 'j'),
      'month' => format_date($report->end, 'custom', 'n'),
      'year' => format_date($report->end, 'custom', 'Y'),
    ),
  );

  // Build an array of order status options.
  $options = array();
  foreach (commerce_order_statuses() as $order_status) {
    $options[$order_status['name']] = check_plain($order_status['title']);
  }
  
  $form['order_statuses'] = array(
    '#type' => 'select',
    '#title' => t('Order statuses'),
    '#description' => t('Which order statuses should be included in the report.'),
    '#default_value' => $report->order_statuses,
    '#options' => $options,
    '#multiple' => TRUE,
  );
  
  $form['actions'] = array(
    '#type' => 'actions',
    'submit' => array(

    ),
  );
  
  switch ($op) {
    case 'add':
      $form['actions']['generate'] = array(
        '#type' => 'submit',
        '#value' => t('Create and generate'),
        '#submit' => array('commerce_reports_tax_form_submit_save', 'commerce_reports_tax_form_submit_generate'),
      );
      $form['actions']['save'] = array(
        '#type' => 'submit',
        '#value' => t('Create'),
        '#submit' => array('commerce_reports_tax_form_submit_save'),
      );
      break;
      
    default:
      $form['actions']['save'] = array(
        '#type' => 'submit',
        '#value' => t('Save'),
        '#submit' => array('commerce_reports_tax_form_submit_save'),
      );
  }
  
  $form['actions']['cancel'] = array(
    '#markup' => l(t('Cancel'), 'admin/commerce/reports/tax'),
  );
  
  return $form;
}

function commerce_reports_tax_form_submit_save($form, &$form_state) {
  $report = entity_ui_form_submit_build_entity($form, $form_state);
  
  commerce_reports_tax_save($report);
  $form_state['redirect'] = 'admin/commerce/reports/tax';
}

function commerce_reports_tax_form_submit_generate($form, &$form_state) {
  $report = entity_ui_form_submit_build_entity($form, $form_state);
  
  commerce_reports_tax_generate($report);
}