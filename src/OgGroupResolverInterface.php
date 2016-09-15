<?php

namespace Drupal\og;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for OgGroupResolver plugins.
 *
 * These plugins are used to discover which groups are relevant in the current
 * context. Each plugin is responsible for finding groups in a particular
 * domain. For example, we can have a plugin that checks if we are on the
 * canonical URL of a group entity, and can take the group entity from the
 * route object.
 *
 * Sometimes a plugin might return multiple relevant groups, for example if it
 * finds a group content entity on the route that belongs to multiple groups.
 *
 * These plugins are invoked by OgContext::getRuntimeContexts() which then
 * interprets the results and makes an educated guess at the group which is most
 * relevant in the current context.
 *
 * @see \Drupal\og\ContextProvider\OgContext::getRuntimeContexts()
 */
interface OgGroupResolverInterface {

  /**
   * Returns the groups that were resolved by the plugin.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of groups.
   */
  public function getGroups();

  /**
   * Returns the group that is most relevant in the plugin's context.
   *
   * A plugin can have enough domain specific knowledge to determine with
   * certainty that a particular group is the most relevant in a certain domain.
   * This method can be used to return that group.
   *
   * If the plugin doesn't have absolute certainty that a particular group is
   * the most relevant, this will return NULL.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The group that is the best candidate, or NULL if there is no best
   *   candidate.
   */
  public function getBestCandidate();

  /**
   * Declares that no further group resolving is necessary.
   *
   * Use this if the plugin has determined the relevant group in the current
   * context with 100% certainty.
   */
  public function stopPropagation();

  /**
   * Returns whether the group resolving process can be stopped.
   *
   * @return bool
   *   TRUE if no further group resolving is necessary. FALSE otherwise.
   */
  public function isPropagationStopped();

}
