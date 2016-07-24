<?php

namespace Drupal\og;

/**
 * @file
 * Contains \Drupal\og\OgContextHandlerInterface.
 */

use Drupal\Core\Entity\ContentEntityBase;

/**
 * Interface OgContextHandlerInterface
 *
 * @package Drupal\og
 */
interface OgContextHandlerInterface {

  /**
   * Return only plugins which active in the negotiation schema.
   */
  const RETURN_ONLY_ACTIVE = 1;

  /**
   * Return only plugins which stored in the negotiation schema.
   */
  const RETURN_ONLY_IN_STORAGE = 2;

  /**
   * Return all the plugins without considering the negotiation schema.
   */
  const RETURN_ALL = 3;

  /**
   * Get the current viewed group.
   *
   * @return ContentEntityBase
   *   Return the group(s) object(s) which match the log of of the plugin.
   */
  public function getGroup();

  /**
   * Get a list of an OG context plugins.
   *
   * @param int $return_mode
   *   Determine which plugins will be returned:
   *
   *    OgContextHandlerInterface::RETURN_ALL - will return all the plugins.
   *      Including plugins which don't have a corresponding OG context
   *      negotiation entity entry.
   *
   *    OgContextHandlerInterface::RETURN_ONLY_ACTIVE - will return only
   *      plugins which their corresponding OG context negotiation entity entry
   *      status is set to true.
   *
   *    OgContextHandlerInterface::RETURN_ONLY_IN_STORAGE - will return only
   *      plugins with a corresponding OG context negotiation entity entry no
   *      matter what is the status.
   *
   * @return array
   *   List of OG context plugins.
   */
  public function getPlugins($return_mode);

  /**
   * Create an instance of an OG context plugin.
   *
   * @param string $plugin_id
   *   The plugin ID.
   *
   * @return OgContextBase
   *   An OG context plugins instance.
   */
  public function getPlugin($plugin_id);

  /**
   * Update plugin settings.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $config
   *   The plugins settings.
   *
   * @return bool
   *   True or false of the update has succeeded.
   */
  public function updatePlugin($plugin_id, $config = []);

  /**
   * Iterate over the plugins and register a new plugin.
   *
   * Will be invoked when installing OG and clearing the cache.
   */
  public function updateConfigStorage();

}
