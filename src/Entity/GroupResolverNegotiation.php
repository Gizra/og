<?php

namespace Drupal\og\Entity;

/**
 * @file
 * Contains \Drupal\og\Entity\GroupResolverNegotiation.
 */

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\og\GroupResolverNegotiationInterface;

/**
 * Defines the OG group resolver negotiation entity.
 *
 * @ConfigEntityType(
 *   id = "group_resolver_negotiation",
 *   label = @Translation("OG group resolver negotiation"),
 *   config_prefix = "group_resolver_negotiation",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class GroupResolverNegotiation extends ConfigEntityBase implements GroupResolverNegotiationInterface {

  /**
   * The OG group resolver negotiation ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The OG group resolver negotiation label.
   *
   * @var string
   */
  protected $label;

  /**
   * The status of the entity.
   *
   * @var boolean
   */
  protected $status;

  /**
   * The description of the plugin.
   *
   * @var string
   */
  protected $description;

  /**
   * The weight of the plugin.
   *
   * @var integer
   */
  protected $weight;

}
