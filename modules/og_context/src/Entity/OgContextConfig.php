<?php

namespace Drupal\og_context\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the OG context config entity.
 *
 * @ConfigEntityType(
 *   id = "og_context_config",
 *   label = @Translation("OG context config"),
 *   handlers = {
 *     "list_builder" = "Drupal\og_context\OgContextConfigListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\og_context\OgContextConfigHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "og_context_config",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/config/og/og_context_config"
 *   }
 * )
 */
class OgContextConfig extends ConfigEntityBase implements OgContextConfigInterface {

  /**
   * The OG context config ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The OG context config label.
   *
   * @var string
   */
  protected $label;

}
