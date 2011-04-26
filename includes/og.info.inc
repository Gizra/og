<?php
// $Id$

/**
 * @file
 * Provides Entity metadata integration.
 */

/**
 * Extend the defaults.
 */
class OgMetadataController extends EntityDefaultMetadataController {

  public function entityPropertyInfo() {
    $info = parent::entityPropertyInfo();
    $properties = &$info[$this->type]['properties'];

    $properties['gid'] += array(
      'required' => TRUE,
      'type' => 'group',
    );

    $properties['entity'] = array(
      'label' => t('Entity'),
      'type' => 'entity',
      'description' => t('The entity that is a group.'),
      'getter callback' => 'og_entity_getter',
      // TODO:
      // 'setter callback' => 'field_collection_item_set_host_entity',
      'required' => TRUE,
    );

    $properties['entity_type'] += array(
      'required' => TRUE,
      'options list' => 'og_get_fieldable_entity_list',
    );

    $properties['created']['type'] = 'date';

    $properties['state'] += array(
      'setter callback' => 'entity_metadata_verbatim_set',
      'options list' => 'og_group_states',
    );

    // Custom properties.
    $properties['members'] = array(
      'label' => t("Members"),
      'type' => 'list<user>',
      'description' => t("A list of members in group."),
      'getter callback' => 'og_get_properties',
    );

    $properties['url'] = array(
      'label' => t('URL'),
      'type' => 'uri',
      'description' => t("The URL to the entity that is a group."),
      'getter callback' => 'og_get_properties',
    );

    return $info;
  }
}

/**
 * Extend the defaults.
 */
class OgMembershipMetadataController extends EntityDefaultMetadataController {

  public function entityPropertyInfo() {
    $info = parent::entityPropertyInfo();
    $properties = &$info[$this->type]['properties'];

    $properties['group'] = array(
      'label' => t("Group"),
      'type' => 'group',
      'description' => t("The group associated with the group membership."),
      'required' => TRUE,
      'getter callback' => 'entity_property_getter_method',
      'schema field' => 'gid',
    );

    $properties['entity'] = array(
      'label' => t('Entity'),
      'type' => 'entity',
      'description' => t('The entity that is a group.'),
      'getter callback' => 'og_entity_getter',
      'required' => TRUE,
      'schema field' => 'etid',
    );

    $properties['entity_type'] += array(
      'required' => TRUE,
      'options list' => 'og_get_fieldable_entity_list',
    );

    $properties['created']['type'] = 'date';

    $properties['state'] += array(
      'setter callback' => 'entity_metadata_verbatim_set',
      'options list' => 'og_group_content_states',
    );

    return $info;
  }
}