<?php
// $Id:$

/**
 * @file
 * Provides support for the Views module.
 */

/**
 * Implements hook_views_data().
 */
function og_views_data() {
  $data = array();

  $data['og_groups']['table']['group'] = t('Organic groups');

  // Advertise this table as a possible base table
  $data['og_groups']['table']['base'] = array(
    'field' => 'gid',
    'title' => t('Organic groups'),
    'help' => t("Groups can be different entities that have other content associated with them."),
  );

  $data['og_groups']['gid'] = array(
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

  $data['og_groups']['entity_type'] = array(
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

  $data['og_groups']['eid'] = array(
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

  $data['og_groups']['state'] = array(
    'title' => t("Group's state"),
    'help' => t('The state of the group.'),
    'field' => array(
      'handler' => 'views_handler_field_xss',
     ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'og_handler_filter_group_state',
      'numeric' => TRUE,
      'allow empty' => FALSE,
      'help' => t('Select the state of the group.'),
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  $data['og_groups']['created'] = array(
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

  $data['og_groups']['member_count'] = array(
    'title' => t('Members count'),
    'field' => array(
      'handler' => 'og_handler_field_group_member_count',
      'click sortable' => FALSE,
      'help' => t('Number of authenticated and approved members for a group. Excludes memberships which are pending approval or blocked.'),
    ),
  );

  return $data;
}

/**
 * Implements hook_views_handlers().
 */
function og_views_handlers() {
  return array(
    // Field
    'og_handler_field_group_member_count' => array(
      'parent' => 'views_handler_field_numeric',
    ),
    // Filter
    'og_handler_filter_group_state' => array(
      'parent' => 'views_handler_filter_in_operator',
    ),
    // Relationship
    'og_handler_relationship' => array(
      'parent' => 'views_handler_relationship',
    ),
  );
}

/**
 * Implements hook_views_plugins().
 */
function og_views_plugins() {
  return array(
    'argument default' => array(
      'og_context' => array(
        'title' => t('Current group from context'),
        'handler' => 'og_plugin_argument_default_og_context',
        'path' => drupal_get_path('module', 'og') . '/includes',
        'parent' => 'fixed', // so that the parent class is included
      ),
    ),
  );
}

/**
 * Implements hook_views_data_alter().
 */
function og_views_data_alter(&$data) {
  $items = module_invoke_all('og_views_relationship');

  foreach ($items as $item) {
    $entity = entity_get_info($item['entity']);
    $title = t('@entity group', array('@entity' => ucfirst($entity['label'])));
    $help = t('Add information on groups that belong to the @entity entity.', array('@entity' => ucfirst($entity['label'])));

    // Add the organic groups relationship.
    $data[$item['views table']]['og_rel'] = array(
      'group' => t('Organic groups'),
      'title' => $title,
      'help' => $help,
      'relationship' => array(
        'entity' => $item['entity'],
        'handler' => 'og_handler_relationship',
        'label' => t('organic groups'),
        'base' => 'og_groups',
        'base field' => 'eid',
        'relationship field' => $item['join field'],
      ),
    );
  }
}