<?php

/**
 * @file
 * Integration with the Views module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_views_data_alter().
 */
function og_views_data_alter(array &$data) {
  // Add relationship to og_membership from users.
  $data['users']['og_membership']['relationship'] = [
    'real field' => 'uid',
    'base' => 'og_membership',
    'base field' => 'uid',
    'label' => t('OG Membership'),
    'title' => t('OG Membership'),
    'id' => 'standard',
  ];
}

/**
 * Implements hook_field_views_data().
 *
 * This is an almost verbatim copy of core_field_views_data() except for the
 * field type check.
 */
function og_field_views_data(FieldStorageConfigInterface $field_storage) {
  $data = views_field_default_views_data($field_storage);
  // This is the same as entity reference integration as the OG standard
  // reference item is no different really.
  switch ($field_storage->getType()) {
    case 'og_standard_reference':
      $entity_type_manager = \Drupal::entityTypeManager();
      $entity_type_id = $field_storage->getTargetEntityTypeId();
      /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = $entity_type_manager->getStorage($entity_type_id)->getTableMapping();
      foreach ($data as $table_name => $table_data) {
        // Add a relationship to the target entity type.
        $target_entity_type_id = $field_storage->getSetting('target_type');
        $target_entity_type = $entity_type_manager->getDefinition($target_entity_type_id);
        $entity_type_id = $field_storage->getTargetEntityTypeId();
        $entity_type = $entity_type_manager->getDefinition($entity_type_id);
        $target_base_table = $target_entity_type->getDataTable() ?: $target_entity_type->getBaseTable();
        $field_name = $field_storage->getName();
        // Provide a relationship for the entity type with the entity reference
        // field.
        $args = [
          '@label' => $target_entity_type->getLabel(),
          '@field_name' => $field_name,
        ];
        $data[$table_name][$field_name]['relationship'] = [
          'title' => t('@label referenced from @field_name', $args),
          'label' => t('@field_name: @label', $args),
          'group' => $entity_type->getLabel(),
          'help' => t('Appears in: @bundles.', ['@bundles' => implode(', ', $field_storage->getBundles())]),
          'id' => 'standard',
          'base' => $target_base_table,
          'entity type' => $target_entity_type_id,
          'base field' => $target_entity_type->getKey('id'),
          'relationship field' => $field_name . '_target_id',
        ];
        // Provide a reverse relationship for the entity type that is referenced
        // by the field.
        $args['@entity'] = $entity_type->getLabel();
        $args['@label'] = $target_entity_type->getSingularLabel();
        $pseudo_field_name = 'reverse__' . $entity_type_id . '__' . $field_name;
        $data[$target_base_table][$pseudo_field_name]['relationship'] = [
          'title' => t('@entity using @field_name', $args),
          'label' => t('@field_name', ['@field_name' => $field_name]),
          'group' => $target_entity_type->getLabel(),
          'help' => t('Relate each @entity with a @field_name set to the @label.', $args),
          'id' => 'entity_reverse',
          'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
          'entity_type' => $entity_type_id,
          'base field' => $entity_type->getKey('id'),
          'field_name' => $field_name,
          'field table' => $table_mapping->getDedicatedDataTableName($field_storage),
          'field field' => $field_name . '_target_id',
          'join_extra' => [
            [
              'field' => 'deleted',
              'value' => 0,
              'numeric' => TRUE,
            ],
          ],
        ];
      }
      break;
  }
  return $data;
}
