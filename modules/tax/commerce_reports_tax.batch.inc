<?php

function _commerce_reports_tax_create($report, &$context) {
  $context['message'] = t('Preparing generation of %title tax report.', array('%title', $report->title));
  
  // Delete all existing data from cache.
  commerce_reporst_tax_cache_reset($report);
  
  // Create query object that will be used in the next step. It should contain all the parameters
  // like order status and time period that the report should reflect.
  $query = new EntityFieldQuery();
  $query
    ->entityCondition('entity_type', 'commerce_order')
    ->propertyCondition('status', $report->order_statuses)
    ->propertyCondition('created', $report->start, '>=')
    ->propertyCondition('created', $report->end + 86400, '<=')
    ->entityOrderBy('entity_id', 'ASC');
  
  $context['results'] = array(
    'report' => &$report,
    'rates' => array(),
    'query' => $query,
    'orders' => array()
  );
}

/**
 * Build the table of tax information.
 *
 * TODO You should be able to generate multiple reports, and it should be able to store these next to each other.
 * TODO All of these reports should be able to be generated with different parameters, like period and entity_type.
 * TODO Make actual use of the Batch API.
 */
function _commerce_reports_tax_generate(&$context) {
  $report =& $context['results']['report'];
  
  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current'] = 0;
    
    $maxQuery = clone $context['results']['query'];
    $context['sandbox']['max'] = $maxQuery
      ->count()
      ->execute();
  }
  
  watchdog('commerce_reports_tax', print_r($context['sandbox'], true));
  
  $rates =& $context['results']['rates'];
  $query = clone $context['results']['query'];

  // As the order of the query is ascending, we just add an extra condition that filters all the previous orders out.
  // We also limit the query to a certain amount of orders for this iteration.
  $result = $query
    ->entityCondition('entity_id', $context['sandbox']['current'], '>')
    ->range(0, 1)
    ->execute();
    
  if ($result) {
    $orders = entity_load('commerce_order', array_keys($result['commerce_order']));

    // Iterate the orders and break each order down into its components.
    $taxes = array();
    foreach ($orders as $order) {
      $wrapper = entity_metadata_wrapper('commerce_order', $order);
      $order_data = $wrapper->commerce_order_total->data->value();
      
      $taxed = array();
      $base_price = 0;

      // Loop through all the components of this order, to retrieve all applies tax rates and 
      // to determine the base price of the order.
      foreach ($order_data['components'] as $component) {
        if (!empty($component['price']['data']['tax_rate'])) {
          // If this component contains a tax rate, process and store.
          $rate = $component['price']['data']['tax_rate'];
          
          // Save this tax rate if we don't know it already.
          // We will write its details to the databae in the finished callback.
          if (empty($rates[$rate['name']])) {
            $rates[$rate['name']] = $rate;
            
            // Create the field that will store the aggregated values for the taxable
            // and taxed amount. These fields contain arrays keyed on currency code.
            $rates[$rate['name']]['aggregates'] = array();
          }

          // For each applied tax rate to this order, create a new object to hold the information.
          // If the report is detailled, we will also use this object to save to the database.
          $tax = new stdClass;
          
          // Report_id is a dummy field at this moment. In the future we want to be able to distinguish different reports.
          $tax->report_id = $report->report_id;
          $tax->order_id = $wrapper->order_id->value();
          $tax->tax = $rate['name'];
          $tax->currency_code = $wrapper->commerce_order_total->currency_code->value();
          $tax->taxed = (int) $component['price']['amount'];

          // Store the record to an array.
          // We still need to add the taxable price and we can only be sure
          // that we know this information after this loop finished running.
          $taxed[] = $tax;
        } elseif ($component['name'] == 'base_price') {
          // Aggregate the base price for this order.
          $base_price += $component['price']['amount'];
        }
      }
      
      // Loop through all the applied taxes to this order to aggregate their values and store them if desired.
      foreach ($taxed as $tax) {
        // Add the taxable price.
        $tax->taxable = $base_price;
        
        // Prepare the fields where we will aggregate the data.
        if (empty($rates[$tax->tax]['aggregates'][$tax->currency_code])) {
          $rates[$tax->tax]['aggregates'][$tax->currency_code] = array(
            'taxable' => 0,
            'taxed' => 0,
          );
        }
        
        // Aggregate the data.
        $rates[$tax->tax]['aggregates'][$tax->currency_code]['taxable'] += $tax->taxable;
        $rates[$tax->tax]['aggregates'][$tax->currency_code]['taxed'] += $tax->taxed;
        
        // If a detailled report which includes information about all orders during a tax timeframe.
        if ($report->detailled) {
          // Write the record to the database.
          drupal_write_record('commerce_reports_tax_rate_orders', $tax);
        }
      }
      
      $context['results']['orders'][] = $order->order_id;
      $context['sandbox']['progress']++;
      $context['sandbox']['current'] = $order->order_id;
    }
  }
  
  // Report back to the user.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

function _commerce_reports_tax_finished($success, $results, $operations) {
  if ($success) {
    $report =& $results['report'];
    $rates =& $results['rates'];
    
    // Store all tax rates we encountered to the database, also store the aggregated data contained within them.
    foreach ($rates as $rate) {
      // Store the tax rate.
      $row = new stdClass;
      
      // Report_id is a dummy field at this moment. In the future we want to be able to distinguish different reports.
      $row->report_id = $report->report_id;
      $row->name = $rate['name'];
      $row->type = $rate['type'];
      $row->title = $rate['title'];
      $row->rate = $rate['rate'];
      
      // Write the record.
      drupal_write_record('commerce_reports_tax_rate', $row);
      
      // Store aggregated data.
      foreach ($rate['aggregates'] as $currency_code => $data) {
        $aggregate = new stdClass;
        
        $aggregate->report_id = $row->report_id;
        $aggregate->tax = $row->name;
        $aggregate->currency_code = $currency_code;
        $aggregate->taxable = $data['taxable'];
        $aggregate->taxed = $data['taxed'];
        
        // Write the record.
        drupal_write_record('commerce_reports_tax_rate_aggregate', $aggregate);
      }
    }
    
    watchdog('commerce_reports_tax', print_r($results['orders'], true));
  } else {
    // TODO Destroy any data we might have generated.
  }
}