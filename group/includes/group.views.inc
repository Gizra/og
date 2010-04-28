<?php
// $Id:$

/**
 * @file
 * Provides support for the Views module.
 */

/**
 * Implements hook_views_data().
 */
function group_views_data() {
  $data = array();

  $data['group_groups']['table']['group'] = t('Group');

  // Advertise this table as a possible base table
  $data['group_groups']['table']['base'] = array(
    'field' => 'gid',
    'title' => t('Group'),
    'help' => t("Groups can be different entities that have other content associated with them."),
  );

  $data['group_groups']['gid'] = array(
    'title' => t('Group ID'),
    'help' => t('The group unique ID.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  $data['group_groups']['entity_type'] = array(
    'title' => t("Group's entity type"),
    'help' => t('The entity type of the group.'),
    'field' => array(
      'handler' => 'views_handler_field_xss',
     ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  $data['group_groups']['etid'] = array(
    'title' => t("Group's entity ID"),
    'help' => t('The entity ID of the group.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  $data['group_groups']['state'] = array(
    'title' => t("Group's state"),
    'help' => t('The state of the group.'),
    'field' => array(
      'handler' => 'views_handler_field_xss',
     ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'group_handler_filter_group_state',
      'numeric' => TRUE,
      'allow empty' => FALSE,
      'help' => t('Select the state of the group.'),
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  $data['group_groups']['created'] = array(
    'title' => t('Group date'),
    'help' => t('The date the group was created.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );

  $data['group_groups']['member_count'] = array(
    'title' => t('Members count'),
    'field' => array(
      'handler' => 'group_handler_field_group_member_count',
      'click sortable' => FALSE,
      'help' => t('Number of authenticated and approved members for a group. Excludes memberships which are pending approval or blocked.'),
    ),
  );

  return $data;
}

/**
 * Implements hook_views_handlers().
 */
function group_views_handlers() {
  return array(
    // Field
    'group_handler_field_group_member_count' => array(
      'parent' => 'views_handler_field_numeric',
    ),
    // Filter
    'group_handler_filter_group_state' => array(
      'parent' => 'views_handler_filter_in_operator',
    ),
    // Relationship
    'group_handler_relationship' => array(
      'parent' => 'views_handler_relationship',
    ),
    // Arguments
    'group_handler_argument_group_audience_gid' => array(
      'parent' => 'views_handler_argument_numeric',
    ),
  );
}

/**
 * Implements hook_views_plugins().
 */
function group_views_plugins() {
  return array(
    'argument default' => array(
      'group_context' => array(
        'title' => t('Current group from context'),
        'handler' => 'group_plugin_argument_default_group_context',
        'path' => drupal_get_path('module', 'group') . '/includes/views',
        'parent' => 'fixed', // so that the parent class is included
      ),
    ),
  );
}

/**
 * Implements hook_views_data_alter().
 */
function group_views_data_alter(&$data) {
  $items = module_invoke_all('group_views_relationship');

  foreach ($items as $item) {
    $entity = entity_get_info($item['entity']);
    $title = t('@entity group', array('@entity' => ucfirst($entity['label'])));
    $help = t('Add information on groups that belong to the @entity entity.', array('@entity' => ucfirst($entity['label'])));

    // Add the group relationship.
    $data[$item['views table']]['group_rel'] = array(
      'group' => t('Group'),
      'title' => $title,
      'help' => $help,
      'relationship' => array(
        'entity' => $item['entity'],
        'handler' => 'group_handler_relationship',
        'label' => t('group'),
        'base' => 'group_groups',
        'base field' => 'etid',
        'relationship field' => $item['join field'],
      ),
    );
  }

  // Override the group audience gid field argument.
  $data['field_data_group_audience']['group_audience_gid']['argument']['handler'] = 'group_handler_argument_group_audience_gid';
}
