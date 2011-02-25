<?php


/**
 * @file
 * Rules integration for the Organic groups module.
 *
 * @addtogroup rules
 * @{
 */

/**
 * Implements hook_rules_action_info().
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
 * Get group members from group audience callback.
 */
function og_rules_get_members($group_audience) {
  $gids = $return = array();
  foreach ($group_audience as $key => $value) {
    $gids[] = $value['gid'];
  }

  if ($gids) {
    $query = new EntityFieldQuery();
    $query
      ->entityCondition('entity_type', 'user')
      ->fieldCondition(OG_AUDIENCE_FIELD, 'gid', $gids, 'IN');

    if ($result = $query->execute()) {
      $return = array_keys($result['user']);
    }
  }

  return array('group_members' => $return);
}

/**
 * @}
 */
