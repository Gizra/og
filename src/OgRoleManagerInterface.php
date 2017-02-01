<?php

namespace Drupal\og;

/**
 * Defines an interface for OG role manager.
 */
interface OgRoleManagerInterface {

  /**
   * Creates the roles for the given group type, based on the default roles.
   *
   * This is intended to be called after a new group type has been created.
   *
   * @param string $entity_type_id
   *   The entity type ID of the group for which to create default roles.
   * @param string $bundle_id
   *   The bundle ID of the group for which to create default roles.
   *
   * @return \Drupal\og\Entity\OgRole[]
   *   Array with the saved OG roles that were created
   */
  public function createPerBundleRoles($entity_type_id, $bundle_id);

  /**
   * Returns the default roles.
   *
   * @return \Drupal\og\Entity\OgRole[]
   *   An associative array of (unsaved) OgRole entities, keyed by role name.
   *   These are populated with the basic properties: name, label, role_type and
   *   is_admin.
   */
  public function getDefaultRoles();

  /**
   * Returns the roles which every group type requires.
   *
   * This provides the 'member' and 'non-member' roles. These are hard coded
   * because they are strictly required and should not be altered.
   *
   * @return \Drupal\og\Entity\OgRole[]
   *   An associative array of (unsaved) required OgRole entities, keyed by role
   *   name. These are populated with the basic properties: name, label and
   *   role_type.
   */
  public function getRequiredDefaultRoles();

  /**
   * Returns all the roles of a provided group.
   *
   * @param string $entity_type_id
   *    The entity type id of the group.
   * @param string $bundle
   *    The bundle of the group.
   *
   * @return \Drupal\og\OgRoleInterface[]
   *   An array of roles indexed by their ids.
   */
  public function getRolesByBundle($entity_type_id, $bundle);

  /**
   * Deletes the roles associated with a group type.
   *
   * @param string $entity_type_id
   *   The entity type ID of the group for which to delete the roles.
   * @param string $bundle_id
   *   The bundle ID of the group for which to delete the roles.
   */
  public function removeRoles($entity_type_id, $bundle_id);

}
