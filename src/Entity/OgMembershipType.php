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
 *   }
 * )
 */
class OgMembershipType extends ConfigEntityBase implements OgMembershipTypeInterface {

  /**
   * The membership type.
   *
   * @var string
   */
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
