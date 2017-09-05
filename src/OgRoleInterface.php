<?php

namespace Drupal\og;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface defining an OG user role entity.
 *
 * Class cannot extend RoleInterface due to PHP 5 limitations.
 */
interface OgRoleInterface {

  /**
   * The role name of the group non-member.
   */
  const ANONYMOUS = 'non-member';

  /**
   * The role name of the group member.
   */
  const AUTHENTICATED = 'member';

  /**
   * The role name of the group administrator.
   */
  const ADMINISTRATOR = 'administrator';

  /**
   * Role type for required roles.
   *
   * This is intended for the 'non-member' and 'member' roles. These cannot be
   * changed or deleted.
   */
  const ROLE_TYPE_REQUIRED = 'required';

  /**
   * Role type for standard roles that are editable and deletable.
   */
  const ROLE_TYPE_STANDARD = 'standard';

  /**
   * Sets the ID of the role.
   *
   * @param string $id
   *   The machine name of the role.
   *
   * @return $this
   */
  public function setId($id);

  /**
   * Returns the label.
   *
   * @return string
   *   The label.
   */
  public function getLabel();

  /**
   * Sets the label.
   *
   * @param string $label
   *   The label to set.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Returns the group type.
   *
   * @return string
   *   The group type.
   */
  public function getGroupType();

  /**
   * Sets the group type.
   *
   * @param string $group_type
   *   The group type to set.
   *
   * @return $this
   */
  public function setGroupType($group_type);

  /**
   * Returns the group bundle.
   *
   * @return string
   *   The group bundle.
   */
  public function getGroupBundle();

  /**
   * Sets the group bundle.
   *
   * @param string $group_bundle
   *   The group bundle to set.
   *
   * @return $this
   */
  public function setGroupBundle($group_bundle);

  /**
   * Returns the role type.
   *
   * @return string
   *   The role type. One of OgRoleInterface::ROLE_TYPE_REQUIRED or
   *   OgRoleInterface::ROLE_TYPE_STANDARD.
   */
  public function getRoleType();

  /**
   * Sets the role type.
   *
   * @param string $role_type
   *   The role type to set. One of OgRoleInterface::ROLE_TYPE_REQUIRED or
   *   OgRoleInterface::ROLE_TYPE_STANDARD.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown when an invalid role type is given.
   */
  public function setRoleType($role_type);

  /**
   * Returns the role name.
   *
   * A role's name consists of the portion of the ID after the group entity type
   * ID and the group bundle ID.
   *
   * @return string
   *   The role name.
   */
  public function getName();

  /**
   * Sets the role name.
   *
   * @param string $name
   *   The role name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Returns the role represented by the given group and role name.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group for which to return the role.
   * @param string $name
   *   The role name for which to return the role.
   *
   * @return \Drupal\og\OgRoleInterface
   *   The role.
   */
  public static function loadByGroupAndName(EntityInterface $group, $name);

  /**
   * Returns the roles that are associated with the given group type and bundle.
   *
   * @param string $group_entity_type_id
   *   The group entity type ID.
   * @param string $group_bundle_id_id
   *   The group bundle ID.
   *
   * @return \Drupal\og\OgRoleInterface[]
   *   The roles.
   */
  public static function loadByGroupType($group_entity_type_id, $group_bundle_id_id);

  /**
   * Get a role by the group's bundle and role name.
   *
   * @param string $entity_type_id
   *   The group entity type ID.
   * @param string $bundle
   *   The group bundle name.
   * @param string $role_name
   *   The role name.
   *
   * @return \Drupal\og\OgRoleInterface|null
   *   The OG role object, or NULL if a matching role was not found.
   */
  public static function getRole($entity_type_id, $bundle, $role_name);

  /**
   * Returns if this is a default role which is required and cannot be deleted.
   *
   * @return bool
   *   True if this is a default role. False otherwise.
   */
  public function isRequired();

}
