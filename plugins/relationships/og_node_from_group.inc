<?php
// $Id: user_from_node.inc,v 1.7 2010/01/29 20:18:02 merlinofchaos Exp $

/**
 * @file
 * Plugin to provide an relationship handler for node from user.
 */

/**
 * TODO: Can this be made generic to be entity from group?
 */
$plugin = array(
  'title' => t('Node from group'),
  'keyword' => 'node',
  'description' => t('Get the node from a group.'),
  'required context' => new ctools_context_required(t('OG group'), 'og_group'),
  'context' => 'og_node_from_group_context',
);

/**
 * Return a new context based on an existing context.
 */
function og_node_from_group_context($context, $conf) {
  // If unset it wants a generic, unfilled context, which is just NULL.
  if (empty($context->data) || !isset($context->data->gid)) {
    return ctools_context_create_empty('node', NULL);
  }

  if (isset($context->data->gid) && $context->data->entity_type == 'node') {
    if ($node = og_load_entity_from_group($context->data->gid)) {

      // Send it to ctools.
      return ctools_context_create('node', $node);
    }
  }
}
