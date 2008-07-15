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

function og_panels_ct_list_subscribers() {
  $items['og_subscribers'] = array(
    'title' => t('Group members'),
    'icon' => 'images/user-multiple.png',
    'path' => drupal_get_path('module', 'og_panels'). '/',
    'description' => t('The list of group subscribers or just group admins.'),
    'required context' => new panels_required_context(t('Group'), 'group'),
    'category' => array(t('Organic groups'), -5),
  );
  return $items;
}

function og_panels_ct_list_search() {
  $items['og_search'] = array(
    'title' => t('Group search'),
    'icon' => 'images/user-multiple.png',
    'path' => drupal_get_path('module', 'og_panels'). '/',
    'description' => t('Search this group.'),
    'required context' => new panels_required_context(t('Group'), 'group'),
    'category' => array(t('Organic groups'), -5),
  );
  return $items;
}

function og_panels_ct_list_faces() {
  $items['og_faces'] = array(
    'title' => t('Group faces'),
    'icon' => 'images/user-multiple.png',
    'path' => drupal_get_path('module', 'og_panels'). '/',
    'description' => t('Show a grid of user pictures.'),
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

function og_panels_ct_render_callback_subscribers($conf, $panel_args, $context) {
  $node = isset($context->data) ? drupal_clone($context->data) : NULL;
  $block->module = 'og_panels';

  $block->subject = t('Group members');
  if ($node) {
    $block->content = og_block_subscribers_list($node->nid, $conf['num_items'], $conf['og_panels_subscribers_is_admin'], $conf['show_more'], $conf['show_picture']);
    $block->delta = $node->nid;
  }
  else {
    $block->content = t('Member list goes here.');
    $block->delta = 'unknown';
  }

  return $block;
}

function og_panels_ct_render_callback_search($conf, $panel_args, $context) {
  $node = isset($context->data) ? drupal_clone($context->data) : NULL;
  $block->module = 'og_panels';

  $block->subject = t('Group search');
  if ($node) {
    if (module_exists('search') && user_access('search content')) {
      $block->content = drupal_get_form('og_search_form', $node);
    }
    $block->delta = $node->nid;
  }
  else {
    $block->content = t('Search form goes here.');
    $block->delta = 'unknown';
  }

  return $block;
}

function og_panels_ct_render_callback_faces($conf, $panel_args, $context) {
  $node = isset($context->data) ? drupal_clone($context->data) : NULL;
  $block->module = 'og_panels';

  $block->subject = t('Group faces');
  if ($node) {
    $block->content = og_list_users_faces_page($node, $conf['column_count'], $conf['row_count'], $conf['pager_id']);
    $block->delta = $node->nid;
  }
  else {
    $block->content = t('Faces go here.');
    $block->delta = 'unknown';
  }

  return $block;
}

function og_panels_content_types_addedit_callback_faces($id, $panel_args, $conf = NULL) {
  $form['column_count'] = array(
    '#type' => 'textfield',
    '#title' => t('Number of pictures per row'),
    '#default_value' => isset($conf['column_count']) ? $conf['column_count'] : 5,
    '#size' => 5,
  );
  $form['row_count'] = array(
    '#type' => 'textfield',
    '#title' => t('Maximum number of rows per page'),
    '#default_value' => isset($conf['row_count']) ? $conf['row_count'] : 10,
    '#size' => 5,
  );
  $form['pager_id'] = array(
    '#type' => 'textfield',
    '#title' => t('Pager ID'),
    '#default_value' => isset($conf['pager_id']) ? $conf['pager_id'] : 0,
    '#size' => 5,
  );
  return $form;
}

function og_panels_content_types_addedit_callback_subscribers($id, $panel_args, $conf = NULL) {
  $form['num_items'] = array(
    '#type' => 'textfield',
    '#title' => t('Number of members'),
    '#description' => t('Maxmimum number of members that should appear on the list'),
    '#default_value' => isset($conf['num_items']) ? $conf['num_items'] : 10,
    '#size' => 5,
  );
  $form['og_panels_subscribers_is_admin'] = array(
    '#type' => 'checkboxes', 
    '#title' => t('Group roles'), 
    '#default_value' => isset($conf['og_panels_subscribers_is_admin']) ? $conf['og_panels_subscribers_is_admin'] : array('members','admins'), 
    '#options' => array('members' => t('Standard members'), 'admins' => t('Administrators')), 
    '#description' => t('You may specify which types of group members appear in this listing.'),
  );
  $form['show_more'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show more link'),
    '#default_value' => isset($conf['show_more']) ? $conf['show_more'] : TRUE,
    '#description' => t('Should a <em>more</em> link appear when more members are in the group but not listed.'),
  );
  $form['show_picture'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show pictures'),
    '#default_value' => isset($conf['show_picture']) ? $conf['show_picture'] : og_is_picture(),
    '#description' => t('Should member pictures be shown in addition to member names.'),
  );
  return $form;
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

function og_panels_ct_title_callback_subscribers($conf, $context) {
  return t('Group members');
}

function og_panels_ct_title_callback_search($conf, $context) {
  return t('Search');
}

function og_panels_ct_title_callback_faces($conf, $context) {
  return t('Faces');
}

