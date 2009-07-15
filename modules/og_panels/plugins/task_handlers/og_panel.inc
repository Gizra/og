<?php
// $Id$

/**
 * @file
 *
 * This is the task handler plugin to handle attaching a panel to any
 * task that advertises itself as a 'context' type, which all of the
 * basic page tasks provided by page_manager.module do by default.
 *
 * Most of our config is handled by panels page_manager.inc, but we do have
 * to customize it a little bit to match what we're doing with this task.
 */

/**
 * Specialized implementation of hook_page_manager_task_handlers().
 */
function og_panels_og_panel_page_manager_task_handlers() {
  return array(
    'handler type' => 'node_view',
    'title' => t('OG Panels'),
    'admin summary' =>'og_panels_og_panel_admin_summary',
    'render' => 'og_panels_og_panel_render',
    'visible' => TRUE,
    'add features' => array(
      'criteria' => t('Selection rules'),
    ),
    'edit forms' => array(
      'criteria' => t('Selection rules'),
    ),
    'operations' => array(
      'criteria' => array(
        'title' => t('Selection rules'),
        'description' => t('Control the criteria used to decide whether or not this variant is used.'),
        'form' => array(
          'order' => array(
            'form' => t('Selection rules'),
          ),
          'forms' => array(
            'form' => array(
              'include' => drupal_get_path('module', 'ctools') . '/includes/context-task-handler.inc',
              'form id' => 'ctools_context_handler_edit_criteria',
            ),
          ),
        ),
      ),
    ),
    'forms' => array(
      'criteria' => array(
        'include' => drupal_get_path('module', 'ctools') . '/includes/context-task-handler.inc',
        'form id' => 'ctools_context_handler_edit_criteria',
      ),
    ),
    'default conf' => array(
      'autogenerate_title' => FALSE,
      'title' => t('OG Panels'),
    ),
  );
}

/**
 * Check selection rules and, if passed, render the contexts.
 *
 * We must first check to ensure the node is of a type we're allowed
 * to render. If not, decline to render it by returning NULL.
 */
function og_panels_og_panel_render($handler, $base_contexts, $args, $test = TRUE) {
  $node = $base_contexts['argument_nid_1']->data;
  $tabs = og_panels_node_data($node->nid);
  if (is_array($tabs)) {
    foreach($tabs as $tab) {
      if ($tab['default_page'] == 1) {
        $info = array(
          'title' => $node->title,
          'content' => og_panels_page($tab['nid'], $tab['tab_num'], FALSE),
          'no_blocks' => !$tab['show_blocks'],
        );
        return $info;
      }
    }
  }
}


/**
 * Provide a nice little summary of this task handler.
 */
function og_panels_og_panel_admin_summary($task, $subtaskd) {
  $output = '<h4>Makes OG panels work.</h4>';

  return $output;
}

/**
 * General settings for the panel
 */
function og_panels_og_panel_edit_settings(&$form, &$form_state) {
  $conf = $form_state['handler']->conf;
  $form['conf']['title'] = array(
    '#type' => 'textfield',
    '#default_value' => $conf['title'],
    '#title' => t('Administrative title'),
    '#description' => t('Administrative title of this variant.'),
  );

  $form['conf']['no_blocks'] = array(
    '#type' => 'checkbox',
    '#default_value' => $conf['no_blocks'],
    '#title' => t('Disable Drupal blocks/regions'),
    '#description' => t('Check this to have the page disable all regions displayed in the theme. Note that some themes support this setting better than others. If in doubt, try with stock themes to see.'),
  );

  $form['conf']['css_id'] = array(
    '#type' => 'textfield',
    '#size' => 35,
    '#default_value' => $conf['css_id'],
    '#title' => t('CSS ID'),
    '#description' => t('The CSS ID to apply to this page'),
  );

  $form['conf']['css'] = array(
    '#type' => 'textarea',
    '#title' => t('CSS code'),
    '#description' => t('Enter well-formed CSS code here; this code will be embedded into the page, and should only be used for minor adjustments; it is usually better to try to put CSS for the page into the theme if possible. This CSS will be filtered for safety so some CSS may not work.'),
    '#default_value' => $conf['css'],
  );
}
