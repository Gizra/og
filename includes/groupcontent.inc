<?php

function og_panels_ct_list_mission() {
  $items['og_mission'] = array(
    'title' => t('Welcome message'),
    'icon' => 'user-multiple.png',
    'path' => drupal_get_path('module', 'og_panels'). '/',
    'description' => t('The welcome message for the group. Specified on group edit form'),
    'required context' => new panels_required_context(t('Group'), 'group'),
    'category' => array(t('Organic groups'), -10),
    
  );
  return $items;
}

function og_panels_ct_list_description() {
  $items['og_description'] = array(
    'title' => t('Group description'),
    'icon' => 'user-multiple.png',
    'path' => drupal_get_path('module', 'og_panels'). '/',
    'description' => t('The group description as specified on the group edit form.'),
    'required context' => new panels_required_context(t('Group'), 'group'),
    'category' => array(t('Organic groups'), -5),
  );
  return $items;
}

 function og_panels_ct_render_callback_mission($conf, $panel_args, $context) {
  $node = isset($context->data) ? drupal_clone($context->data) : NULL;
  $block->module = 'og_panels';

  $block->subject = t('Welcome');
  if ($node) {
    $block->content = check_markup($node->body, $node->format, FALSE);
    $block->delta = $node->nid;
  }
  else {
    $block->content = t('Welcome statement goes here.');
    $block->delta = 'unknown';
  }

  return $block;
}

function og_panels_ct_render_callback_description($conf, $panel_args, $context) {
  $node = isset($context->data) ? drupal_clone($context->data) : NULL;
  $block->module = 'og_panels';

  $block->subject = t('Description');
  if ($node) {
    $block->content = check_markup($node->og_description);
    $block->delta = $node->nid;
  }
  else {
    $block->content = t('Description goes here.');
    $block->delta = 'unknown';
  }

  return $block;
}

function og_panels_ct_title_callback_mission($conf, $context) {
  return t('Welcome');
}

function og_panels_ct_title_callback_description($conf, $context) {
  return t('Description');
}
