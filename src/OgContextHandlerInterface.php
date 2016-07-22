<?php

namespace Drupal\og;

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
   * Get a list of an OG context plugins.
   *
   * @param array $config
   * @return array
   */
  public function getPlugins($config = []);

  /**
   * Create an instance of an OG context plugin.
   *
   * @param $plugin_id
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
