<?php

namespace Drupal\og;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for classes providing a collection of resolved groups.
 *
 * This is intended for holding the groups that are discovered by
 * OgGroupResolver plugins. When this class is instantiated it initially doesn't
 * hold any groups. It is then passed to each OgGroupResolver plugin which can
 * add groups to the collection, or remove groups from it. After the final
 * plugin has finished its work the resulting list of groups can be used to
 * determine the group context.
 *
 * This class also has some additional features that are intended to help with
 * determining the correct group context from a given collection of groups.
 * Several plugins might discover the same group inside their domain, so adding
 * a group multiple times by different plugins will increase the likelihood that
 * this group will be ultimately chosen. Each time a group is added in fact a
 * vote is cast in its favor. Each vote carries a certain weight. In normal
 * circumstances the weight will be determined by the plugin's priority and will
 * be set by calling ::setVoteWeight() before instantiating the plugin. A plugin
 * is free to override the vote weight if they consider a group they discovered
 * is particularly noteworthy.
 *
 * Plugins can also declare their search domain by passing along the cache
 * context IDs of the domain they operate in. This will ensure that the proper
 * cache contexts will be present on the group that is finally set as route
 * context.
 *
 * @todo Implement the passing of cache context IDs.
 *
 * See OgResolvedGroupCollection for the main implementation of this class,
 * OgContext::getRunTimeContext() for the main consumer of the collections, and
 * the various OgGroupResolver plugins for examples on how to add and remove
 * groups in a collection.
 *
 * @see \Drupal\og\ContextProvider\OgContext::getRuntimeContexts()
 * @see \Drupal\og\OgGroupResolverInterface
 */
interface OgResolvedGroupCollectionInterface {

  /**
   * Adds a group to the collection.
   *
   * Groups can be added multiple times by different OgGroupResolver plugins.
   * Each time it is added a 'vote' will be cast in its favor, increasing the
   * chance this group will be chosen as the group context.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $group
   *   The group to add.
   * @param string[] $cache_contexts
   *   An optional array of cache contexts to assign to the group.
   * @param int $weight
   *   The weight to assign to this vote. If omitted the default weight will be
   *   used that is set with ::setVoteWeight().
   */
  public function addGroup(ContentEntityInterface $group, array $cache_contexts = [], $weight = NULL);

  /**
   * Returns information about the groups in the collection.
   *
   * @return array
   *   An array of groups. Each item will be an associative array with the
   *   following keys:
   *   - entity: the group entity.
   *   - votes: an array of votes that have been cast for this entity.
   *   - cache_contexts: an array of cache contexts that were used to discover
   *     this group.
   */
  public function getGroupInfo();

  /**
   * Removes the given group from the collection.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $group
   *   The group to remove.
   */
  public function removeGroup(ContentEntityInterface $group);

  /**
   * Returns whether the given group has already been added.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $group
   *   The group for which to check the existence.
   *
   * @return bool
   *   TRUE if the group has already been added.
   */
  public function hasGroup(ContentEntityInterface $group);

  /**
   * Gets the current default vote weight.
   *
   * @return int
   *   The weight that is being assigned to votes being cast.
   */
  public function getVoteWeight();

  /**
   * Sets the default weight of the votes that are added by OgResolver plugins.
   *
   * The plugins are ordered by priority. The votes cast by a plugin with a
   * lower priority will have a lower weight in the final calculation.
   *
   * The vote weight defaults to 0 when the class is instantiated.
   *
   * @param int $weight
   *   The weight to assign to votes being cast.
   */
  public function setVoteWeight($weight);

  /**
   * Sorts the groups in the collection according to their vote count.
   *
   * The weight of each vote is taken into account.
   */
  public function sort();

}
