<?php

namespace Drupal\og;

/**
 * Declares an interface for OG context providers.
 */
interface OgContextInterface {

  /**
   * Returns the group which is relevant in the current context, if any.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The group which is relevant in the current context, or NULL if no group
   *   was found.
   */
  public function getGroup();

}
