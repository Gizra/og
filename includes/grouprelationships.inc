<?php
// $Id$
/**
 * @file includes/grouprelationships.inc
 *
 * Plugin to provide an relationship handler for nodes posted to a group.
 */

/**
 * Return a new context based on an existing context
 */
function panels_group_from_node_context($context = NULL, $conf) {
  // If unset it wants a generic, unfilled context, which is just NULL
  if (empty($context->data)) {
    return panels_context_create_empty('group', NULL);
  }

  if ($group = og_get_group_context()) {
    return panels_context_create('group', $group);
  }
}