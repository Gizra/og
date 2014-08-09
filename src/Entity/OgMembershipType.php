<?php

/**
 * Contain the OG membership type entity definition. This will be a config
 * entity.
 */

namespace Drupal\og\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * @ConfigEntityType(
 *   id = "og_membership_type",
 *   label = @Translation("OG membership type"),
 *   config_prefix = "og_membership_type",
 *   bundle_of = "og_membership",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "name"
 *   }
 * )
 */
class OgMembershipType extends ConfigEntityBase implements ConfigEntityInterface {

  protected $type;

  /**
   * Return the ID of the entity.
   *
   * @return int|null|string
   */
  public function id() {
    return $this->type;
  }
}