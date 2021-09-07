<?php

/**
 * @file
 * Post-update functions for the Organic groups module.
 */

declare(strict_types = 1);

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\og\OgMembershipInterface;

/**
 * Apply schema updates on the 'state' field of 'og_membership' entity.
 */
function og_post_update_og_membership_state_field(&$sandbox) {
  // The 'state' base field of the 'og_membership' entity was changed from a
  // 'string' field to a 'list_string' type field. This implementation of
  // hook_post_update_NAME() follows the guidance in docs, linked below,
  // to update the field's storage and resolve the entity field definition
  // mismatch which would otherwise be reported in the drupal Status Report
  // page.
  // @see https://www.drupal.org/docs/drupal-apis/update-api/updating-entities-and-fields-in-drupal-8#s-updating-a-base-field-type
  /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $definition_manager */
  $definition_manager = \Drupal::service('entity.definition_update_manager');
  if (!$definition_manager->needsUpdates()) {
    // No updates necessary.
    return new TranslatableMarkup('No entity updates to run.');
  }
  $change_list = $definition_manager->getChangeList();
  if (empty($change_list['og_membership']['field_storage_definitions']['state'])) {
    return new TranslatableMarkup('State field on OG Membership entity is already up to date.');
  }
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $bundle_of = 'og_membership';

  $storage = $entity_type_manager->getStorage($bundle_of);
  $bundle_definition = $entity_type_manager->getDefinition($bundle_of);
  // Set the key fields.
  $entity_id_key = $bundle_definition->getKey('id');
  $field_key = 'state';
  // If there is no data table defined then use the base table.
  $table_name = $storage->getDataTable() ?: $storage->getBaseTable();
  $database = \Drupal::database();

  // Store the existing values in a variable.
  $state_values = $database->select($table_name)
    ->fields($table_name, [$entity_id_key, $field_key])
    ->execute()
    ->fetchAllKeyed();

  // Clear out the values.
  $database->update($table_name)
    ->fields([$field_key => NULL])
    ->execute();

  // Uninstall the field.
  $field_storage_definition = $definition_manager->getFieldStorageDefinition($field_key, $bundle_of);
  $definition_manager->uninstallFieldStorageDefinition($field_storage_definition);

  // Create a new field definition.
  $new_state_field = BaseFieldDefinition::create('list_string')
    ->setLabel(new TranslatableMarkup('State'))
    ->setDescription(new TranslatableMarkup('The user membership state: active, pending, or blocked.'))
    ->setDefaultValue(OgMembershipInterface::STATE_ACTIVE)
    ->setSettings([
      'allowed_values' => [
        OgMembershipInterface::STATE_ACTIVE => new TranslatableMarkup('Active'),
        OgMembershipInterface::STATE_PENDING => new TranslatableMarkup('Pending'),
        OgMembershipInterface::STATE_BLOCKED => new TranslatableMarkup('Blocked'),
      ],
    ])
    ->setDisplayOptions('form', [
      'type' => 'options_buttons',
      'weight' => 0,
    ])
    ->setDisplayConfigurable('view', TRUE)
    ->setDisplayConfigurable('form', TRUE)
    ->setRequired(TRUE);

  // Install the new definition.
  $definition_manager->installFieldStorageDefinition($field_key, $bundle_of, $bundle_of, $new_state_field);

  // Restore the values.
  foreach ($state_values as $id => $value) {
    $database->update($table_name)
      ->fields([$field_key => $value])
      ->condition($entity_id_key, $id)
      ->execute();
  }
  return new TranslatableMarkup('State field on OG Membership entity was updated.');
}
