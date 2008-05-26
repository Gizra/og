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

function og_panels_context_group_settings_form($conf, $external = FALSE) {
  if ($external) {
    $form['external'] = array(
      '#type' => 'checkbox',
      '#default_value' => $conf['external'],
      '#title' => t('Require this context from an external source (such as containing panel page).'),
      '#description' => t('If selected, group selection (below) will be ignored'),
    );
  }


  $form['node'] = array(
    '#prefix' => '<div class="no-float">',
    '#suffix' => '</div>',
    '#title' => t('Enter the NID of a group'),
    '#type' => 'textfield',
    '#maxlength' => 512,
//    '#autocomplete_path' => 'panels/node/autocomplete',
    '#weight'  => -10,
  );

  if (!empty($conf['nid'])) {
    $info = db_fetch_object(db_query("SELECT * FROM {node} n WHERE n.nid = %d", $conf['nid']));
    if ($info) {
      $form['node']['#description'] = t('Currently set to "%title"', array('%title' => $info->title));
    }
  }

  $form['nid'] = array(
    '#type' => 'value',
    '#value' => $conf['nid'],
  );

  return $form;
}

/**
 * Validate a node.
 */
function og_panels_context_group_settings_form_validate($form, $form_values) {
  // Validate the autocomplete
  if (empty($form_values['external']) && empty($form_values['nid']) && empty($form_values['node'])) {   
    form_error($form['node'], t('You must select a node.'));
    return;
  }

  if (empty($form_values['node'])) {
    return;
  }

  $nid = $form_values['node'];
  $preg_matches = array();
  $match = preg_match('/\[nid: (\d+)\]/', $nid, $preg_matches);
  if (!$match) {
    $match = preg_match('/^nid: (\d+)/', $nid, $preg_matches);
  }

  if ($match) {
    $nid = $preg_matches[1];
  }
  if (is_numeric($nid)) {
    $node = db_fetch_object(db_query(db_rewrite_sql("SELECT n.nid, n.type FROM {node} n WHERE n.nid = %d"), $nid));
  }
  else {
    $node = db_fetch_object(db_query(db_rewrite_sql("SELECT n.nid, n.type FROM {node} n WHERE LOWER(n.title) = LOWER('%s')"), $nid));
  }

  if (!$node) {
    form_error($form['node'], t('Invalid group selected.'));
  }
  elseif (!og_is_group_type($node->type)) {
    form_error($form['node'], t('Node is of type %type which not a group type.', array('%type' => $node->type)));
  }
  else {
    form_set_value($form['nid'], $node->nid);
  }

}