<?php

namespace Drupal\og\Controller;

class OG {

  /**
   * Define active group content states.
   *
   * When a user has this membership state they are considered to be of
   * "member" role.
   */
  const STATE_ACTIVE = 1;

  /**
   * Define pending group content states. The user is subscribed to the group
   * but isn't an active member yet.
   *
   * When a user has this membership state they are considered to be of
   * "non-member" role.
   */
  const STATE_PENDING = 2;

  /**
   * Define blocked group content states. The user is rejected from the group.
   *
   * When a user has this membership state they are denided access to any
   * group related action. This state, however, does not prevent user to
   * access a group or group content node.
   */
  const STATE_BLOCKED = 3;

  /**
   * Group audience field.
   */
  const AUDIENCE_FIELD = 'og_group_ref';

  /**
   * Group field.
   */
  const GROUP_FIELD = 'group_group';

  /**
   * Group default roles and permissions field.
   */
  const DEFAULT_ACCESS_FIELD = 'og_roles_permissions';

  /**
   * The role name of group non-members.
   */
  const ANONYMOUS_ROLE = 'non-member';

  /**
   * The role name of group member.
   */
  const AUTHENTICATED_ROLE = 'member';

  /**
   * The role name of group administrator.
   */
  const ADMINISTRATOR_ROLE = 'administrator member';

  /**
   * The default group membership type that is the bundle of group membership.
   */
  const MEMBERSHIP_TYPE_DEFAULT = 'og_membership_type_default';

  /**
   * The name of the user's request field in the default group membership type.
   */
  const MEMBERSHIP_REQUEST_FIELD = 'og_membership_request';

  /**
   * Create an organic groups field in a bundle.
   *
   * @param $field_name
   *   The field name
   * @param $entity_type
   *   The entity type
   * @param $bundle
   *   The bundle name.
   * @param $og_field
   *   (optional) Array with field definitions, to allow easier overriding by
   *   the caller. If empty, function will get the field info by calling
   *   OG::FieldsInfo() with the field name.
   */
  public static function CreateField($field_name, $entity_type, $bundle, $og_field = array()) {
    if (empty($og_field)) {
      $og_field = og_fields_info($field_name);
    }

    $field = field_info_field($field_name);
    // Allow overriding the field name.
    $og_field['field']['field_name'] = $field_name;
    if (empty($field)) {
      $field = field_create_field($og_field['field']);
    }

    $instance = field_info_instance($entity_type, $field_name, $bundle);
    if (empty($instance)) {
      $instance = $og_field['instance'];
      $instance += array(
        'field_name' => $field_name,
        'bundle' => $bundle,
        'entity_type' => $entity_type,
      );

      field_create_instance($instance);
      // Clear the entity property info cache, as OG fields might add different
      // entity property info.
      og_invalidate_cache();
      entity_property_info_cache_clear();
    }
  }

  /**
   * Get all the modules fields that can be assigned to fieldable entities.
   *
   * @param $field_name
   *   The field name that was registered for the definition.
   *
   * @return bool
   *   An array with the field and instance definitions, or FALSE if not
   */
  function FieldsInfo($field_name = NULL) {
    $return = &drupal_static(__FUNCTION__, array());

    if (empty($return)) {
      foreach (module_implements('og_fields_info') as $module) {
        if ($fields = module_invoke($module, 'og_fields_info')) {
          foreach ($fields as $key => $field) {
            // Add default values.
            $field += array(
              'entity type' => array(),
              'multiple' => FALSE,
              'description' => '',
            );

            // Add the module information.
            $return[$key] = array_merge($field, array('module' => $module));
          }
        }
      }

      // Allow other modules to alter the field info.
      drupal_alter('og_fields_info', $return);
    }

    if (!empty($field_name)) {
      return !empty($return[$field_name]) ?  $return[$field_name] : FALSE;
    }

    return $return;
  }
}
