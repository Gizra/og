<?php

/**
 * @file
 * Provides Entity metadata integration.
 */

/**
 * Extend the default OG membership metadata properties.
 */
class OgMembershipMetadataController extends EntityDefaultMetadataController {

  public function entityPropertyInfo() {
    $info = parent::entityPropertyInfo();
    $properties = &$info[$this->type]['properties'];

    // Initialize values to prevent WSOD, no certain cases of clear cache.
    $properties = !empty($properties) ? $properties : array();
    $properties += array(
      'entity_type' => array(),
      'group_type' => array(),
      'field_name' => array(),
      'language' => array(),
    );

    $properties['gid'] += array(
      'setter callback' => 'entity_property_verbatim_set',
      'required' => TRUE,
    );

    $properties['group'] = array(
      'label' => t("OG group"),
      'type' => 'entity',
      'description' => t("The OG group associated with the OG membership."),
      'getter callback' => 'og_entity_getter',
      'setter callback' => 'og_entity_setter',
      'setter permission' => 'administer group',
      'required' => TRUE,
    );

    // Add a property for all group entity types, in order to use tokens for
    // these entities. See https://drupal.org/node/1973686
    foreach (og_get_all_group_entity() as $entity_type => $entity_label) {
      $properties['group__' . $entity_type] = array(
        'label' => $entity_label,
        'type' => $entity_type,
        'description' => t('The OG group when the group is a !type.', array(
          '!type' => $entity_label,
        )),
        'getter callback' => 'og_entity_getter',
        'setter callback' => 'og_entity_setter',
        'setter permission' => 'administer group',
      );
    }

    $properties['etid'] += array(
      'setter callback' => 'entity_property_verbatim_set',
      'required' => TRUE,
    );

    $properties['entity'] = array(
      'label' => t('Entity'),
      'type' => 'entity',
      'description' => t('The entity that is a group content.'),
      'getter callback' => 'og_entity_getter',
      'setter callback' => 'og_entity_setter',
      'setter permission' => 'administer group',
      'required' => TRUE,
    );

    // Add a property for all group content entity types, in order to use tokens
    // for these entities. See https://drupal.org/node/1973686
    foreach (og_get_all_group_content_entity() as $entity_type => $entity_label) {
      $properties['entity__' . $entity_type] = array(
        'label' => $entity_label,
        'type' => $entity_type,
        'description' => t('The OG entity that is a group content when it is a !type.', array(
          '!type' => $entity_label,
        )),
        'getter callback' => 'og_entity_getter',
        'setter callback' => 'og_entity_setter',
        'setter permission' => 'administer group',
      );
    }

    $properties['created']['type'] = 'date';
    $properties['created'] += array(
      'setter callback' => 'entity_property_verbatim_set',
    );

    $properties['state']['type'] = 'integer';
    $properties['state'] += array(
      'setter callback' => 'entity_property_verbatim_set',
      'options list' => 'og_group_content_states',
      'required' => TRUE,
    );

    $properties['entity_type'] += array(
      'setter callback' => 'entity_property_verbatim_set',
    );

    $properties['type']['type'] = 'og_membership_type';
    $properties['type'] += array(
      'required' => TRUE,
      'setter callback' => 'entity_property_verbatim_set',
    );

    $properties['group_type'] += array(
      'setter callback' => 'entity_property_verbatim_set',
      'required' => TRUE,
      'options list' => 'og_get_all_group_entity',
    );

    $properties['field_name'] += array(
      'setter callback' => 'entity_property_verbatim_set',
      // TODO: Add options list to give a list of group-audience fields.
      // 'options list' => '',
    );

    $properties['language'] += array(
      'setter callback' => 'entity_property_verbatim_set',
    );

    return $info;
  }
}
