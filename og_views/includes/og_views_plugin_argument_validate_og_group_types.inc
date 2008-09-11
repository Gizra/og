<?php
/**
 * Validate whether an argument is a group node. Borrows heavily form the Node argument validator.
 */
class og_views_plugin_argument_validate_og_group_types extends views_plugin_argument_validate {
  // What does this do?
  var $option_name = 'validate_argument_og_group_types';

  function validate_form(&$form, &$form_state) {
    $form['validate_argument_nid_type'] = array(
      '#type' => 'select',
      '#title' => t('Argument type'),
      '#options' => array(
        'nid' => t('Node ID'),
        'nids' => t("Node ID's separated by , or +"),
      ),
      '#default_value' => isset($this->argument->options['validate_argument_nid_type']) ? $this->argument->options['validate_argument_nid_type'] : 'nid',
      '#process' => array('views_process_dependency'),
      '#dependency' => array('edit-options-validate-type' => array($this->id)),
    );
    
    $form['validate_argument_is_member'] = array(
      '#type' => 'checkbox',
      '#title' => t('Validate current user is a member of a specified group'),
      '#default_value' => !empty($this->argument->options['validate_argument_is_member']),
      '#process' => array('views_process_dependency'),
      '#dependency' => array('edit-options-validate-type' => array($this->id)),
    );
  }

  function validate_argument($argument) {
    $types = og_get_types('group');

    $type = isset($this->argument->options['validate_argument_nid_type']) ? $this->argument->options['validate_argument_nid_type'] : 'nid';
    switch ($type) {
      case 'nid':
        if (!is_numeric($argument)) {
          return FALSE;
        }
        $node = node_load($argument);
        if (!$node) {
          return FALSE;
        }

        if (!og_is_group_type($node->type)) {
          return FALSE;
        }
        
        if (!empty($this->argument->options['validate_argument_is_member'])) {
          if (!og_is_group_member($node->nid)) {
            return FALSE;
          }
        }

        // Save the title() handlers some work.
        $this->argument->validated_title = check_plain($node->title);

        // Admin has not setup any content types to behave as a group. Thats unsupported.
        if (empty($types)) {
          return TRUE;
        }
        
        return TRUE;
      case 'nids':
        $nids = new stdClass();
        $nids->value = array($argument);
        $nids = views_break_phrase($argument, $nids);
        if ($nids->value == -1) {
          return FALSE;
        }

        $placeholders = implode(', ', array_fill(0, sizeof($nids->value), '%d'));

        $has_membership = FALSE;
        $titles = array();
        $test_nids = drupal_map_assoc($nids->value);

        $result = db_query("SELECT nid, type, title FROM {node} WHERE nid IN ($placeholders)", $nids->value);
        while ($node = db_fetch_object($result)) {
          if (!og_is_group_type($node->type)) {
            return FALSE;
          }

          if (!empty($this->argument->options['validate_argument_is_member'])) {
            // If user is a member of any of the specified groups, then she has access.
            if (!$has_membership && og_is_group_member($node->nid)) {
              $has_membership = TRUE;
            }
          }
          else {
            $has_membership = TRUE;
          }

          $titles[] = check_plain($node->title);
          unset($test_nids[$node->nid]);
        }

        $this->argument->validated_title = implode($nids->operator == 'or' ? ' + ' : ', ', $titles);
        // If $test is not empty, we did not find a nid.
        return empty($test_nids) && $has_membership;
    }
  }
}