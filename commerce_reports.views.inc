<?php

/**
 * @file
 * Views integration for Commerce reports.
 */

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\entity\BundleFieldDefinition;

/**
 * Implements hook_views_data_alter().
 *
 * @todo This should be ported to the Entity API module.
 */
function commerce_reports_views_data_alter(array &$data) {
  // Data that could be generalized for other plugin types.
  $entity_type_id = 'commerce_order_report';
  $base_table = 'commerce_order_report';

  $container = \Drupal::getContainer();

  // Get the list of bundles the field appears in.
  $bundles = array_keys($container->get('entity_type.bundle.info')->getBundleInfo($entity_type_id));
  $table_mapping = $container->get('entity_type.manager')->getStorage($entity_type_id)->getTableMapping();
  $entity_type = $container->get('entity_type.manager')->getDefinition($entity_type_id);
  $group_name = $entity_type->getLabel();

  // Get the list of field definitions for all bundles.
  $all_field_definitions = [];
  foreach ($bundles as $bundle) {
    $field_definitions = $container->get('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle);
    $all_field_definitions += $field_definitions;
  }
  $all_field_definitions = array_filter($all_field_definitions, function (FieldDefinitionInterface $field_definition) {
    return ($field_definition instanceof BundleFieldDefinition);
  });

  foreach ($all_field_definitions as $field_definition) {
    $field_storage = $field_definition->getFieldStorageDefinition();
    $field_columns = $field_storage->getColumns();
    $field_name = $field_storage->getName();
    $entity_type_id = $field_storage->getTargetEntityTypeId();

    // @todo Generalize this code to make it work with any table layout. See
    //   https://www.drupal.org/node/2079019.
    $field_tables = [
      EntityStorageInterface::FIELD_LOAD_CURRENT => [
        'table' => $table_mapping->getDedicatedDataTableName($field_storage),
        'alias' => "{$entity_type_id}__{$field_name}",
      ],
    ];
    // Build the relationships between the field table and the entity tables.
    $table_alias = $field_tables[EntityStorageInterface::FIELD_LOAD_CURRENT]['alias'];
    // If there is no data table, just join directly.
    $data[$table_alias]['table']['join'][$base_table] = [
      'table' => $table_mapping->getDedicatedDataTableName($field_storage),
      'left_field' => $entity_type->getKey('id'),
      'field' => 'entity_id',
      'extra' => [
        ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
      ],
    ];

    // Build the list of additional fields to add to queries.
    $add_fields = ['delta', 'langcode', 'bundle'];
    foreach (array_keys($field_columns) as $column) {
      $add_fields[] = $table_mapping->getFieldColumnName($field_storage, $column);
    }
    // Determine the label to use for the field. We don't have a label available
    // at the field level, so we just go through all fields and take the one
    // which is used the most frequently.
    $label = $field_name;
    $label_counter = [];
    $all_labels = [];
    $bundles_names = [];
    // Count the amount of fields per label per field storage.
    foreach ($bundles as $bundle) {
      $bundle_fields = $container->get('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle);
      if (isset($bundle_fields[$field_name])) {
        $bundles_names[] = $bundle;
        $field = $bundle_fields[$field_name];
        $label = $field->getLabel();
        $label_key = $label->getUntranslatedString();
        $label_counter[$label_key] = isset($label_counter[$label_key]) ? ++$label_counter[$label_key] : 1;
        $all_labels[$label_key] = $label;
      }
    }
    if (!empty($label_counter)) {
      // Sort the field labels by it most used label and return the most used one.
      // If the counts are equal, sort by the label to ensure the result is
      // deterministic.
      uksort($label_counter, function ($a, $b) use ($label_counter) {
        if ($label_counter[$a] === $label_counter[$b]) {
          return strcmp($a, $b);
        }
        return $label_counter[$a] > $label_counter[$b] ? -1 : 1;
      });
      $label_counter = array_keys($label_counter);
      $label_key = $label_counter[0];
      $label = $all_labels[$label_key];
    }

    // Expose data for the field as a whole.
    foreach ($field_tables as $type => $table_info) {
      $table = $table_info['table'];
      $table_alias = $table_info['alias'];

      if ($type == EntityStorageInterface::FIELD_LOAD_CURRENT) {
        $group = $group_name;
        $field_alias = $field_name;
      }
      else {
        $group = t('@group (historical data)', ['@group' => $group_name]);
        $field_alias = $field_name . '-revision_id';
      }

      $data[$table_alias][$field_alias] = [
        'group' => $group,
        'title' => $label,
        'title short' => $label,
        'help' => t('Appears in: @bundles.', ['@bundles' => implode(', ', $bundles_names)]),
      ];

      // Go through and create a list of aliases for all possible combinations of
      // entity type + name.
      $aliases = [];
      $also_known = [];
      foreach ($all_labels as $label_name => $true) {
        if ($type == EntityStorageInterface::FIELD_LOAD_CURRENT) {
          if ($label != $label_name) {
            $aliases[] = [
              'base' => $base_table,
              'group' => $group_name,
              'title' => $label_name,
              'help' => t('This is an alias of @group: @field.', ['@group' => $group_name, '@field' => $label]),
            ];
            $also_known[] = t('@group: @field', ['@group' => $group_name, '@field' => $label_name]);
          }
        }
        elseif ($supports_revisions && $label != $label_name) {
          $aliases[] = [
            'base' => $table,
            'group' => t('@group (historical data)', ['@group' => $group_name]),
            'title' => $label_name,
            'help' => t('This is an alias of @group: @field.', ['@group' => $group_name, '@field' => $label]),
          ];
          $also_known[] = t('@group (historical data): @field', ['@group' => $group_name, '@field' => $label_name]);
        }
      }
      if ($aliases) {
        $data[$table_alias][$field_alias]['aliases'] = $aliases;
        // The $also_known variable contains markup that is HTML escaped and that
        // loses safeness when imploded. The help text is used in #description
        // and therefore XSS admin filtered by default. Escaped HTML is not
        // altered by XSS filtering, therefore it is safe to just concatenate the
        // strings. Afterwards we mark the entire string as safe, so it won't be
        // escaped, no matter where it is used.
        // Considering the dual use of this help data (both as metadata and as
        // help text), other patterns such as use of #markup would not be correct
        // here.
        $data[$table_alias][$field_alias]['help'] = Markup::create($data[$table_alias][$field_alias]['help'] . ' ' . t('Also known as:') . ' ' . implode(', ', $also_known));
      }

      $keys = array_keys($field_columns);
      $real_field = reset($keys);
      $data[$table_alias][$field_alias]['field'] = [
        'table' => $table,
        'id' => 'field',
        'field_name' => $field_name,
        'entity_type' => $entity_type_id,
        // Provide a real field for group by.
        'real field' => $field_alias . '_' . $real_field,
        'additional fields' => $add_fields,
        // Default the element type to div, let the UI change it if necessary.
        'element type' => 'div',
        'is revision' => $type == EntityStorageInterface::FIELD_LOAD_REVISION,
      ];
    }

    // Expose data for the field as a whole.
    foreach ($field_tables as $type => $table_info) {
      $table = $table_info['table'];
      $table_alias = $table_info['alias'];

      if ($type == EntityStorageInterface::FIELD_LOAD_CURRENT) {
        $group = $group_name;
        $field_alias = $field_name;
      }
      else {
        $group = t('@group (historical data)', ['@group' => $group_name]);
        $field_alias = $field_name . '-revision_id';
      }

      $data[$table_alias][$field_alias] = [
        'group' => $group,
        'title' => $label,
        'title short' => $label,
        'help' => t('Appears in: @bundles.', ['@bundles' => implode(', ', $bundles_names)]),
      ];

      // Go through and create a list of aliases for all possible combinations of
      // entity type + name.
      $aliases = [];
      $also_known = [];
      foreach ($all_labels as $label_name => $true) {
        if ($type == EntityStorageInterface::FIELD_LOAD_CURRENT) {
          if ($label != $label_name) {
            $aliases[] = [
              'base' => $base_table,
              'group' => $group_name,
              'title' => $label_name,
              'help' => t('This is an alias of @group: @field.', ['@group' => $group_name, '@field' => $label]),
            ];
            $also_known[] = t('@group: @field', ['@group' => $group_name, '@field' => $label_name]);
          }
        }
        elseif ($supports_revisions && $label != $label_name) {
          $aliases[] = [
            'base' => $table,
            'group' => t('@group (historical data)', ['@group' => $group_name]),
            'title' => $label_name,
            'help' => t('This is an alias of @group: @field.', ['@group' => $group_name, '@field' => $label]),
          ];
          $also_known[] = t('@group (historical data): @field', ['@group' => $group_name, '@field' => $label_name]);
        }
      }
      if ($aliases) {
        $data[$table_alias][$field_alias]['aliases'] = $aliases;
        // The $also_known variable contains markup that is HTML escaped and that
        // loses safeness when imploded. The help text is used in #description
        // and therefore XSS admin filtered by default. Escaped HTML is not
        // altered by XSS filtering, therefore it is safe to just concatenate the
        // strings. Afterwards we mark the entire string as safe, so it won't be
        // escaped, no matter where it is used.
        // Considering the dual use of this help data (both as metadata and as
        // help text), other patterns such as use of #markup would not be correct
        // here.
        $data[$table_alias][$field_alias]['help'] = Markup::create($data[$table_alias][$field_alias]['help'] . ' ' . t('Also known as:') . ' ' . implode(', ', $also_known));
      }

      $keys = array_keys($field_columns);
      $real_field = reset($keys);
      $data[$table_alias][$field_alias]['field'] = [
        'table' => $table,
        'id' => 'field',
        'field_name' => $field_name,
        'entity_type' => $entity_type_id,
        // Provide a real field for group by.
        'real field' => $field_alias . '_' . $real_field,
        'additional fields' => $add_fields,
        // Default the element type to div, let the UI change it if necessary.
        'element type' => 'div',
        'is revision' => $type == EntityStorageInterface::FIELD_LOAD_REVISION,
      ];
    }

    // Expose data for each field property individually.
    foreach ($field_columns as $column => $attributes) {
      $allow_sort = TRUE;

      // Identify likely filters and arguments for each column based on field type.
      switch ($attributes['type']) {
        case 'int':
        case 'mediumint':
        case 'tinyint':
        case 'bigint':
        case 'serial':
        case 'numeric':
        case 'float':
          $filter = 'numeric';
          $argument = 'numeric';
          $sort = 'standard';
          if ($field_storage->getType() == 'boolean') {
            $filter = 'boolean';
          }
          break;
        case 'text':
        case 'blob':
          // It does not make sense to sort by blob or text.
          $allow_sort = FALSE;
        default:
          $filter = 'string';
          $argument = 'string';
          $sort = 'standard';
          break;
      }

      if (count($field_columns) == 1 || $column == 'value') {
        $title = t('@label (@name)', ['@label' => $label, '@name' => $field_name]);
        $title_short = $label;
      }
      else {
        $title = t('@label (@name:@column)', ['@label' => $label, '@name' => $field_name, '@column' => $column]);
        $title_short = t('@label:@column', ['@label' => $label, '@column' => $column]);
      }

      // Expose data for the property.
      foreach ($field_tables as $type => $table_info) {
        $table = $table_info['table'];
        $table_alias = $table_info['alias'];

        if ($type == EntityStorageInterface::FIELD_LOAD_CURRENT) {
          $group = $group_name;
        }
        else {
          $group = t('@group (historical data)', ['@group' => $group_name]);
        }
        $column_real_name = $table_mapping->getFieldColumnName($field_storage, $column);

        // Load all the fields from the table by default.
        $additional_fields = $table_mapping->getAllColumns($table);

        $data[$table_alias][$column_real_name] = [
          'group' => $group,
          'title' => $title,
          'title short' => $title_short,
          'help' => t('Appears in: @bundles.', ['@bundles' => implode(', ', $bundles_names)]),
        ];

        // Go through and create a list of aliases for all possible combinations of
        // entity type + name.
        $aliases = [];
        $also_known = [];
        foreach ($all_labels as $label_name => $true) {
          if ($label != $label_name) {
            if (count($field_columns) == 1 || $column == 'value') {
              $alias_title = t('@label (@name)', ['@label' => $label_name, '@name' => $field_name]);
            }
            else {
              $alias_title = t('@label (@name:@column)', ['@label' => $label_name, '@name' => $field_name, '@column' => $column]);
            }
            $aliases[] = [
              'group' => $group_name,
              'title' => $alias_title,
              'help' => t('This is an alias of @group: @field.', ['@group' => $group_name, '@field' => $title]),
            ];
            $also_known[] = t('@group: @field', ['@group' => $group_name, '@field' => $title]);
          }
        }
        if ($aliases) {
          $data[$table_alias][$column_real_name]['aliases'] = $aliases;
          // The $also_known variable contains markup that is HTML escaped and
          // that loses safeness when imploded. The help text is used in
          // #description and therefore XSS admin filtered by default. Escaped
          // HTML is not altered by XSS filtering, therefore it is safe to just
          // concatenate the strings. Afterwards we mark the entire string as
          // safe, so it won't be escaped, no matter where it is used.
          // Considering the dual use of this help data (both as metadata and as
          // help text), other patterns such as use of #markup would not be
          // correct here.
          $data[$table_alias][$column_real_name]['help'] = Markup::create($data[$table_alias][$column_real_name]['help'] . ' ' . t('Also known as:') . ' ' . implode(', ', $also_known));
        }

        $data[$table_alias][$column_real_name]['argument'] = [
          'field' => $column_real_name,
          'table' => $table,
          'id' => $argument,
          'additional fields' => $additional_fields,
          'field_name' => $field_name,
          'entity_type' => $entity_type_id,
          'empty field name' => t('- No value -'),
        ];
        $data[$table_alias][$column_real_name]['filter'] = [
          'field' => $column_real_name,
          'table' => $table,
          'id' => $filter,
          'additional fields' => $additional_fields,
          'field_name' => $field_name,
          'entity_type' => $entity_type_id,
          'allow empty' => TRUE,
        ];
        if (!empty($allow_sort)) {
          $data[$table_alias][$column_real_name]['sort'] = [
            'field' => $column_real_name,
            'table' => $table,
            'id' => $sort,
            'additional fields' => $additional_fields,
            'field_name' => $field_name,
            'entity_type' => $entity_type_id,
          ];
        }

        // Set click sortable if there is a field definition.
        if (isset($data[$table_alias][$field_name]['field'])) {
          $data[$table_alias][$field_name]['field']['click sortable'] = $allow_sort;
        }

        // Expose additional delta column for multiple value fields.
        if ($field_storage->isMultiple()) {
          $title_delta = t('@label (@name:delta)', ['@label' => $label, '@name' => $field_name]);
          $title_short_delta = t('@label:delta', ['@label' => $label]);

          $data[$table_alias]['delta'] = [
            'group' => $group,
            'title' => $title_delta,
            'title short' => $title_short_delta,
            'help' => t('Delta - Appears in: @bundles.', ['@bundles' => implode(', ', $bundles_names)]),
          ];
          $data[$table_alias]['delta']['field'] = [
            'id' => 'numeric',
          ];
          $data[$table_alias]['delta']['argument'] = [
            'field' => 'delta',
            'table' => $table,
            'id' => 'numeric',
            'additional fields' => $additional_fields,
            'empty field name' => t('- No value -'),
            'field_name' => $field_name,
            'entity_type' => $entity_type_id,
          ];
          $data[$table_alias]['delta']['filter'] = [
            'field' => 'delta',
            'table' => $table,
            'id' => 'numeric',
            'additional fields' => $additional_fields,
            'field_name' => $field_name,
            'entity_type' => $entity_type_id,
            'allow empty' => TRUE,
          ];
          $data[$table_alias]['delta']['sort'] = [
            'field' => 'delta',
            'table' => $table,
            'id' => 'standard',
            'additional fields' => $additional_fields,
            'field_name' => $field_name,
            'entity_type' => $entity_type_id,
          ];
        }
      }
    }
  }
}
