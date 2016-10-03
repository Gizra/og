<?php

namespace Drupal\og;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Contains a collection of groups discovered by OgGroupResolver plugins.
 */
class OgResolvedGroupCollection implements OgResolvedGroupCollectionInterface {

  /**
   * A collection of groups that were resolved by OgGroupResolver plugins.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $groups = [];

  /**
   * Keeps track of the number of times a group was proposed by a plugin.
   *
   * @var int[][]
   */
  protected $votes = [];

  /**
   * The default weight of votes cast by plugins.
   *
   * @var int
   */
  protected $voteWeight = 0;

  /**
   * {@inheritdoc}
   */
  public function addGroup(ContentEntityInterface $group, $weight = NULL) {
    $key = $this->generateKey($group);
    $this->groups[$key] = $group;
    $this->votes[$key][] = $weight !== NULL ? $weight : $this->getVoteWeight();
  }

  /**
   * {@inheritdoc}
   */
  public function hasGroup(ContentEntityInterface $group) {
    $key = $this->generateKey($group);
    return array_key_exists($key, $this->groups);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroups() {
    return $this->groups;
  }

  /**
   * {@inheritdoc}
   */
  public function removeGroup(ContentEntityInterface $group) {
    $key = $this->generateKey($group);
    unset($this->groups[$key]);
    unset($this->votes[$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function getVoteWeight() {
    return $this->voteWeight;
  }

  /**
   * {@inheritdoc}
   */
  public function setVoteWeight($weight) {
    $this->voteWeight = $weight;
  }

  /**
   * Generates a key that can be used to identify the given group.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $group
   *   The group for which to generate the key.
   *
   * @return string
   *   The key.
   */
  protected function generateKey(ContentEntityInterface $group) {
    return $group->getEntityTypeId() . '|' . $group->id();
  }

}
