<?php

/**
 * @file
 * Plugin to provide an relationship handler for OG group content from node.
 */

/**
 * Plugin definition.
 */
$plugin = array(
  'title' => t('OG group from group content node'),
  'keyword' => 'group',
  'description' => t('Get the OG group context from a node that is a group content. The first group the node belongs to will be used.'),
  'required context' => new ctools_context_required(t('Node'), 'node'),
  'context' => 'og_group_from_node_group_content_context',
);

/**
 * Return a new context based on an existing context.
 */
function og_group_from_node_group_content_context($context, $conf) {
  // If unset it wants a generic, unfilled context, which is just NULL.
  if (empty($context->data) || !isset($context->data->nid)) {
    return ctools_context_create_empty('entity:group', NULL);
  }

  if (isset($context->data->nid)) {
    $node = clone $context->data;
    $gids = og_get_entity_groups('node', $node);
    if (!$gids) {
      return;
    }

    // Get the first group.
    $gid = reset($gids);
    $group = og_load($gid);
    dpm($group);

    // Send it to ctools.
    return ctools_context_create('entity:group', $group);
  }
}
