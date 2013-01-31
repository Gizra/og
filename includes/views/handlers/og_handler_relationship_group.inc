<?php


/**
 * @file
 * Contains various relationship handlers.
 */

/**
 * Specialized relationship handler associating groups and their groups type.
 *
 * @ingroup views
 */
class og_handler_relationship_group extends views_handler_relationship {

  /**
   * Called to implement a relationship in a query.
   */
  function query() {
    $entity = $this->definition['entity'];

    $this->definition['extra'][] = array(
      'field' => 'group_type',
      'value' => $this->definition['entity'],
    );
    parent::query();
  }
}
