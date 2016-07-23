<?php

/**
 * @file
 * Contains Drupal\og\Entity\OgContextNegotiation.
 */

namespace Drupal\og\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\og\OgContextNegotiationInterface;

/**
 * Defines the OG context negotiation entity.
 *
 * @ConfigEntityType(
 *   id = "og_context_negotiation",
 *   label = @Translation("OG context negotiation"),
 *   config_prefix = "og_context_negotiation",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class OgContextNegotiation extends ConfigEntityBase implements OgContextNegotiationInterface {

  /**
   * The OG context negotiation ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The OG context negotiation label.
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
