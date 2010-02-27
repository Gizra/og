<?php
// $Id:$

/**
 * @file
 * Provides support for the Views module.
 */

/**
 * Implementation of hook_views_data().
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
      'handler' => 'views_handler_filter_string',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  return $data;
}
