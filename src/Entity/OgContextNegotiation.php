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
 *   handlers = {
 *     "list_builder" = "Drupal\og\OgContextNegotiationListBuilder",
 *     "form" = {
 *       "add" = "Drupal\og\Form\OgContextNegotiationForm",
 *       "edit" = "Drupal\og\Form\OgContextNegotiationForm",
 *       "delete" = "Drupal\og\Form\OgContextNegotiationDeleteForm"
 *     }
 *   },
 *   config_prefix = "og_context_negotiation",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/og_context_negotiation/{og_context_negotiation}",
 *     "edit-form" = "/admin/structure/og_context_negotiation/{og_context_negotiation}/edit",
 *     "delete-form" = "/admin/structure/og_context_negotiation/{og_context_negotiation}/delete",
 *     "collection" = "/admin/structure/visibility_group"
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

}
