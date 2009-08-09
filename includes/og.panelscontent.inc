<?php

function og_panels_ct_list_mission() {
  $items['og_mission'] = array(
    'title' => t('Welcome message'),
    'icon' => 'images/user-multiple.png',
    'path' => drupal_get_path('module', 'og_panels'). '/',
    'description' => t('The welcome message for the group. Specified on group edit form.'),
    'required context' => new panels_required_context(t('Group'), 'group'),
    'category' => array(t('Organic groups'), -10),
    
  );
  return $items;
}

function og_panels_ct_list_description() {
  $items['og_description'] = array(
    'title' => t('Group description'),
    'icon' => 'images/user-multiple.png',
    'path' => drupal_get_path('module', 'og_panels'). '/',
    'description' => t('The group description as specified on the group edit form.'),
    'required context' => new panels_required_context(t('Group'), 'group'),
    'category' => array(t('Organic groups'), -5),
  );
  return $items;
}

function og_panels_ct_render_callback_mission($conf, $panel_args, $context) {
  dargs();
  $node = isset($context->data) ? drupal_clone($context->data) : NULL;
  $block->module = 'og_panels';

  $block->subject = t('Welcome');
  if ($node) {
    // This rendering somewhat verbose technique exactly matches og_view_group().
    $value = check_markup($node->body, $node->format, FALSE);
    $form['og_mission'] = array(
      '#theme' => 'og_mission', 
      '#value' => $value,
      '#node' => $node,
      '#weight' => -3,
    );
    $block->content = drupal_render($form['og_mission']);
    
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

function og_panels_content_types_validate_callback_subscribers($form, $form_values) {
  if (!is_numeric($form_values['num_items'])) {
    form_error($form['num_items'], t('Number of members must be an integer.'));
  }
  
  form_set_value($form['og_panels_subscribers_is_admin'], array_filter($form_values['og_panels_subscribers_is_admin']));
}

function og_panels_content_types_validate_callback_faces($form, $form_values) {
  if (!is_numeric($form_values['column_count'])) {
    form_error($form['column_count'], t('Number of pictures per row must be an integer.'));
  }
  if (!is_numeric($form_values['row_count'])) {
    form_error($form['row_count'], t('Maximum number of rows per page must be an integer.'));
  }
}


function og_panels_ct_title_callback_mission($conf, $context) {
  return t('Welcome');
}

function og_panels_ct_title_callback_description($conf, $context) {
  return t('Description');
}