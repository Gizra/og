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
   * The default weight of votes cast by plugins.
   *
   * @var int
   */
  protected $voteWeight = 0;

  /**
   * {@inheritdoc}
   */
  public function addGroup(ContentEntityInterface $group, array $cache_contexts = [], $weight = NULL) {
    $key = $this->generateKey($group);
    $this->groups[$key]['entity'] = $group;
    $this->groups[$key]['votes'][] = $weight !== NULL ? $weight : $this->getVoteWeight();
    foreach ($cache_contexts as $cache_context) {
      $this->groups[$key]['cache_contexts'][$cache_context] = $cache_context;
    }
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
  public function getGroupInfo() {
    return $this->groups;
  }

  /**
   * {@inheritdoc}
   */
  public function removeGroup(ContentEntityInterface $group) {
    $key = $this->generateKey($group);
    unset($this->groups[$key]);
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
   * {@inheritdoc}
   */
  public function sort() {
    // Find the best matching group by iterating over the candidates and return
    // the one that has the most "votes". If there are multiple candidates with
    // the same number of votes then the candidate that was resolved by the
    // plugin(s) with the highest priority will be returned.
    uasort($this->groups, function ($a, $b) {
      if (count($a['votes']) == count($b['votes'])) {
        return array_sum($a['votes']) < array_sum($b['votes']) ? -1 : 1;
      }
      return count($a['votes']) < count($b['votes']) ? -1 : 1;
    });
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
