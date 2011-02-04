<?php
// $Id$

/**
 * @file rules integration for the node module
 *
 * @addtogroup rules
 * @{
 */

/**
 * Implements hook_rules_action_info() on behalf of the node module.
 */
function og_rules_action_info() {
  $items = array();
  $items['og_get_members'] = array(
    'label' => t('Get group members from group audience'),
    'group' => t('Organic groups'),
    'parameter' => array(
      'group_audience' => array(
        'type' => 'list<field_item_group>',
        'label' => t('Field item group'),
      ),
    ),
    'provides' => array(
      'group_members' => array('type' => 'list<user>', 'label' => t('List of group members')),
    ),
    'base' => 'og_rules_get_members',
    // 'access callback' => 'rules_php_integration_access',
  );
  return $items;
}

/**
 * Node integration access callback.
 */
function og_rules_get_members($group_audience) {
  return array('group_members' => array(1, 2));
}

/**
 * @}
 */
