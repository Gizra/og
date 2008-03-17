<?php
// $Id$
/**
 * @file contexts/group.inc
 *
 * Panels plugin to provide a og group context
 */

/**
 * It's important to remember that $conf is optional here, because contexts
 * are not always created from the UI.
 */
function og_panels_context_create_group($empty, $data = NULL, $conf = FALSE) {
  $context = new panels_context('group');
  $context->plugin = 'group';
  if ($empty) {
    return $context;
  }

  if ($conf) {
    $data = node_load($data['nid']);
  }
  if (!empty($data)) {
    og_set_group_context($data);
    $context->data = $data;
    $context->title = $data->title;
    $context->argument = $data->nid;
    return $context;
  }
}

// NOTE: MUCH of what appears below here is irrelevant to og_panels, at least in the immediate term.

function og_panels_context_group_settings_form($conf, $external = FALSE) {
  if ($external) {
    $form['external'] = array(
      '#type' => 'checkbox',
      '#default_value' => FALSE,
      '#title' => t('Require this context from an external source (such as containing panel page)'),
      '#description' => t('If selected, node selection (below) will be ignored'),
    );
  }


  $form['group'] = array(
    '#prefix' => '<div class="no-float">',
    '#suffix' => '</div>',
    '#title' => t('Enter the name or gid of a group'),
    '#type' => 'textfield',
    '#maxlength' => 512,
    '#autocomplete_path' => 'panels/node/autocomplete', // FIXME this needs to be replaced with a corresponding autocomplete function, to be implemented in panels.module
    '#weight'  => -10,
  );

  if (!empty($conf['gid'])) {
    $info = db_fetch_object(db_query("SELECT * FROM {og} WHERE n.nid = %d", $conf['gid']));
    if ($info) {
      $form['group']['#description'] = t('Currently set to "%title"', array('%title' => $info->title));
    }
  }

  $form['gid'] = array(
    '#type' => 'value',
    '#value' => $conf['nid'],
  );

  return $form;
}

/**
 * Validate a group.
 */
function og_panels_context_group_settings_form_validate($form, $form_values) {
  // Validate the autocomplete
  if (empty($form_values['external']) && empty($form_values['gid']) && empty($form_values['group'])) {   
    form_error($form['group'], t('You must select a group.'));
    return;
  }

  if (empty($form_values['group'])) {
    return;
  }

  $gid = $form_values['group'];
  $preg_matches = array();
  $match = preg_match('/\[gid: (\d+)\]/', $gid, $preg_matches);
  if (!$match) {
    $match = preg_match('/^gid: (\d+)/', $gid, $preg_matches);
  }

  if ($match) {
    $gid = $preg_matches[1];
  }
  if (is_numeric($gid)) {
    $group = db_fetch_object(db_query(db_rewrite_sql("SELECT n.nid FROM {og} n WHERE n.nid = %d"), $gid));
  }
  else {
    $group = db_fetch_object(db_query(db_rewrite_sql("SELECT n.nid FROM {node} n WHERE LOWER(n.description) = LOWER('%s')"), $gid));
  }

  if (!$group) {
    form_error($form['group'], t('Invalid group selected'));
  }
  else {
    form_set_value($form['gid'], $group->nid);
  }

}
