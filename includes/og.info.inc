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
    );

    $properties['etid'] += array(
      'required' => TRUE,
    );

    $properties['entity_type'] += array(
      'required' => TRUE,
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
      'getter callback' => 'og_group_get_properties',
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
      'schema field' => 'gid',
    );

    $properties['created']['type'] = 'date';

    $properties['state'] += array(
      'setter callback' => 'entity_metadata_verbatim_set',
      'options list' => 'og_group_states',
    );

    return $info;
  }
}