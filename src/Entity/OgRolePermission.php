<?php

/**
 * Contain the OG role permission entity definition. This will be a content
 * entity.
 */

namespace Drupal\og\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * OG mange permission for users per group as an autonomic authority on your
 * Drupal's site. Each module that interact with OG, and OG as well, defines a
 * permission, like the core's permission system. OG roles can get permission
 * for each group type and for specific groups.
 *
 * @ContentEntityType(
 *   id = "og_role_permission",
 *   label = @Translation("OG role permission"),
 *   module = "og",
 *   base_table = "og_role_permission",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "permission"
 *   },
 * )
 */
class OgRolePermission extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = array();

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The identifier of the row.'))
      ->setReadOnly(TRUE);

    $fields['rid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Role ID'))
      ->setDescription(t('The role object.'))
      ->setSetting('target_type', 'og_role');

    $fields['permission'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Permission'))
      ->setDescription(t('The permission it self'));

    $fields['module'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Module'))
      ->setDescription(t('The module defining the permission'));

    return $fields;
  }
}