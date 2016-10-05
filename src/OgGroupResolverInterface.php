<?php

namespace Drupal\og;

/**
 * Defines an interface for OgGroupResolver plugins.
 *
 * These plugins are used to discover which groups are relevant in the current
 * context. Each plugin is responsible for finding groups in a particular
 * domain. For example, we can have a plugin that checks if we are on the
 * canonical URL of a group entity, and can take the group entity from the
 * route object.
 *
 * Sometimes a plugin might discover multiple relevant groups, for example if it
 * finds a group content entity on the route that belongs to multiple groups.
 *
 * If a plugin discovers a group that has already been discovered by a previous
 * plugin in the chain, this will cause an additional 'vote' to be registered
 * for the group. If there are multiple groups discovered, the group that has
 * the most 'votes' will be elected as the official 'og' route context.
 *
 * In addition to adding discovered groups and voting for existing ones, a
 * plugin may remove previously discovered groups, for example if it finds a
 * condition in its domain that is incompatible with a particular group. For
 * example a plugin might discover that the current user doesn't have access to
 * a group, or a certain group may not be displayed in the administration
 * section of the site.
 *
 * These plugins are invoked by OgContext::getRuntimeContexts() which will
 * inspect the collection of discovered groups and make an educated guess at the
 * group which is most relevant in the current context.
 *
 * @see \Drupal\og\ContextProvider\OgContext::getRuntimeContexts()
 */
interface OgGroupResolverInterface {

  /**
   * Resolves groups within the plugin's domain.
   *
   * @param \Drupal\og\OgResolvedGroupCollectionInterface $collection
   *   A collection of groups that were resolved by previous plugins. If the
   *   plugin discovers new groups, it may add these to this collection.
   *   A plugin may also remove groups from the collection that were previously
   *   discovered by other plugins, if it finds out that certain groups are
   *   incompatible with the current state in the plugin's domain.
   */
  public function resolve(OgResolvedGroupCollectionInterface $collection);

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
