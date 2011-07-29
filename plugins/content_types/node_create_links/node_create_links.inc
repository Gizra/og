<?php

/**
 * Plugin definition.
 */
$plugin = array(
  'title' => t('OG Content create links'),
  'description' => t('Crafted links to create content (nodes) for a given group.'),
  'required context' => new ctools_context_required(t('OG Group'), 'entity:group'),
  'category' => t('Organic groups'),
);

/**
 * Render callback.
 */
function og_node_create_links_content_type_render($subtype, $conf, $args, $context) {
  if (empty($context->data)) {
    return FALSE;
  }

  $group = $context->data;
  $links = og_node_create_links($group->gid);
  if (!$links) {
    return FALSE;
  }

  $module = 'og';
  $block = new stdClass();
  $block->module = $module;
  $block->title = t('Content create links');

  $block->content = $links;
  return $block;
}

/**
 * Edit form.
 */
function og_node_create_links_content_type_edit_form($form, &$form_state) {
  // provide a blank form so we have a place to have context setting.
  return $form;
}
