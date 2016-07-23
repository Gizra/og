<?php

namespace Drupal\og;

use Drupal\Core\Entity\ContentEntityBase;

interface OgContextHandlerInterface {

  /**
   * Return only plugins which active in the negotiation schema.
   */
  const RETURN_ONLY_ACTIVE = 1;

  /**
   * Return only plugins which stored in he negotiation schema with a status of
   * enabled/disabled.
   */
  const RETURN_ONLY_IN_STORAGE = 2;

  /**
   * Return all the plugins without considering the negotiation schema.
   */
  const RETURN_ALL = 3;

  /**
   * Get the current viewed group.
   *
   * @return NULL|ContentEntityBase
   */
  public function getGroup();

  /**
   * Get a list of an OG context plugins.
   *
   * @param array $config
   *   Array of settings:
   *    sort_by_weight - Determine if the plugins will be order by the weight of
   *    corresponding OG context negotiation entity entry.
   *
   *    return_mode - Determine which plugins will be returned:
   *      OgContextHandlerInterface::RETURN_ALL - will return all the plugins.
   *      Including plugins which don't have a corresponding OG context
   *      negotiation entity entry.
   *
   *     OgContextHandlerInterface::RETURN_ONLY_ACTIVE - will return only
   *      plugins which their corresponding OG context negotiation entity entry
   *      status is set to true.
   *
   *      OgContextHandlerInterface::RETURN_ONLY_IN_STORAGE - will return only
   *      plugins with a corresponding OG context negotiation entity entry no
   *      matter what is the status.
   *
   * @return array
   */
  public function getPlugins($config = []);

  /**
   * Create an instance of an OG context plugin.
   *
   * @param $plugin_id
   *   The plugin ID.
   *
   * @return OgContextBase
   */
  public function getPlugin($plugin_id);

  /**
   * Update plugin settings.
   *
   * @param $plugin_id
   *   The plugin ID.
   * @param array $config
   *   The plugins settings.
   *
   * @return bool
   */
  public function updatePlugin($plugin_id, $config = []);

  /**
   * Iterate over the plugins and register a new plugin.
   *
   * Will be invoked when installing OG and clearing the cache.
   */
  public function updateConfigStorage();

}
