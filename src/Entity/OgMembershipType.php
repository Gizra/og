<?php

namespace Drupal\og\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\og\OgMembershipTypeInterface;

/**
 * The membership type entity.
 *
 * A membership type is the bundle of the OG membership. There is a single
 * "default" bundle that comes out of the box, but others can be created. The
 * use case for membership types, is for example creating a "premium"
 * membership.
 *
 * By having a different membership type, also different fields can be attached,
 * so in our "premium" membership, we could add a date field, to indicate when
 * the subscription should be ended.
 *
 * @ConfigEntityType(
 *   id = "og_membership_type",
 *   label = @Translation("OG membership type"),
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\og\Form\OgMembershipTypeForm",
 *       "edit" = "Drupal\og\Form\OgMembershipTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "list_builder" = "Drupal\og\OgMembershipTypeListBuilder"
 *   },
 *   admin_permission = "administer group",
 *   config_prefix = "og_membership_type",
 *   bundle_of = "og_membership",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "name"
 *   },
 *   config_export = {
 *     "type",
 *     "name",
 *     "description"
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/membership-types/manage/{membership_type}",
 *     "delete-form" = "/admin/structure/membership-types/manage/{membership_type}/delete",
 *     "collection" = "/admin/structure/membership-types",
 *   }
 * )
 */
class OgMembershipType extends ConfigEntityBase implements OgMembershipTypeInterface {

  protected $type;

  /**
   * Return the ID of the entity.
   *
   * @return string|null
   *   The type of the entity.
   */
  public function id() {
    return $this->type;
  }

}
