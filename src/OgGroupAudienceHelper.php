<?php

namespace Drupal\og;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * OG audience field helper methods.
 */
class OgGroupAudienceHelper implements OgGroupAudienceHelperInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs an OgGroupAudienceHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function hasGroupAudienceField($entity_type_id, $bundle_id) {
    return (bool) $this->getAllGroupAudienceFields($entity_type_id, $bundle_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function isGroupAudienceField(FieldDefinitionInterface $field_definition) {
    return $field_definition->getType() == OgGroupAudienceHelperInterface::GROUP_REFERENCE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllGroupAudienceFields($group_content_entity_type_id, $group_content_bundle_id, $group_entity_type_id = NULL, $group_bundle_id = NULL) {
    $return = [];
    $entity_type = $this->entityTypeManager->getDefinition($group_content_entity_type_id);

    if (!$entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      // This entity type is not fieldable.
      return [];
    }

    $field_definitions = $this->entityFieldManager->getFieldDefinitions($group_content_entity_type_id, $group_content_bundle_id);

    foreach ($field_definitions as $field_definition) {
      if (!$this->isGroupAudienceField($field_definition)) {
        // Not a group audience field.
        continue;
      }

      $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');

      if (isset($group_entity_type_id) && $target_type != $group_entity_type_id) {
        // Field doesn't reference this group type.
        continue;
      }

      $handler_settings = $field_definition->getSetting('handler_settings');

      if (isset($group_bundle_id) && !empty($handler_settings['target_bundles']) && !in_array($group_bundle_id, $handler_settings['target_bundles'])) {
        continue;
      }

      $field_name = $field_definition->getName();
      $return[$field_name] = $field_definition;
    }

    return $return;
  }

}
