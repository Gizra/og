<?php


/**
 * @file
 * Provides support for the Views module.
 */

/**
 * "Group" entity Views definition.
 */
class OgViewsController extends EntityDefaultViewsController {

  /**
   * Override views_data().
   */
  public function views_data() {
    $data = parent::views_data();

    $data['og']['gid']['argument'] = array(
      'handler' => 'og_handler_argument_group_audience_gid',
    );

    $data['field_data_group_audience']['user_roles'] = array(
      'group' => t('Group'),
      'title' => t('Group user roles'),
      'help' => t('Show all the groups a user belongs to in a group.'),
      // This is a dummy field, so point it to a real field that we need - the
      // group ID
      'real field' => 'group_audience_gid',
      'field' => array(
        'handler' => 'og_handler_field_user_roles',
      ),
    );

    $data['field_data_group_audience']['og_permissions'] = array(
      'group' => t('Group'),
      'title' => t('Group permissions'),
      'help' => t('Filter by group permissions.'),
      // This is a dummy field, so point it to a real field that we need - the
      // group ID
      'real field' => 'group_audience_gid',
      'field' => array(
        'handler' => 'og_handler_field_group_permissions',
      ),
    );

    return $data;
  }
}

/**
 * "Group membership" entity Views definitions.
 */
class OgMembershipViewsController extends EntityDefaultViewsController {

  /**
   * Override views_data().
   */
  public function views_data() {
    $data = parent::views_data();

    $data['og_membership']['state']['filter'] = array(
      'handler' => 'og_handler_filter_group_state',
      'numeric' => TRUE,
      'allow empty' => FALSE,
      'help' => t('Select the state of the group.'),
    );

    return $data;
  }
}



/**
 * Implements hook_views_handlers().
 */
function og_views_handlers() {
  return array(
    // Base class field.
    'og_handler_field_prerender_list' => array(
     'parent' => 'views_handler_field_prerender_list',
    ),
    // Field
    'og_handler_field_group_audience_state' => array(
      'parent' => 'views_handler_field',
    ),
    'og_handler_field_user_roles' => array(
      'parent' => 'og_handler_field_prerender_list',
    ),
    'og_handler_field_group_permissions' => array(
      'parent' => 'og_handler_field_prerender_list',
    ),
    // Filter
    'og_handler_filter_group_state' => array(
      'parent' => 'views_handler_filter_in_operator',
    ),
    'og_handler_filter_group_audience_state' => array(
      'parent' => 'views_handler_filter_in_operator',
    ),
    // Relationship
    'og_handler_relationship' => array(
      'parent' => 'views_handler_relationship',
    ),
    // Arguments
    'og_handler_argument_group_audience_gid' => array(
      'parent' => 'views_handler_argument_numeric',
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

    // Add the group relationship.
    $data[$item['views table']]['og_rel'] = array(
      'group' => t('Group'),
      'title' => $title,
      'help' => $help,
      'relationship' => array(
        'entity' => $item['entity'],
        'handler' => 'og_handler_relationship',
        'label' => t('group'),
        'base' => 'og',
        'base field' => 'etid',
        'relationship field' => $item['join field'],
      ),
    );
  }
  // TODO: Maybe move field overrides to field_views_data_alter().
  // TODO: Override also field revisions table.

  // Override group audience - "gid" (group ID) field argument.
  $data['field_data_group_audience']['group_audience_gid']['argument']['handler'] = 'og_handler_argument_group_audience_gid';
  $data['field_data_group_audience']['group_audience_gid']['field']['handler'] = 'views_handler_field_numeric';

  // Override group and group membership - "state" filter and add a field.
  foreach (array('og', 'og_membership') as $type) {
    $data[$type]['state']['filter']['handler'] = 'og_handler_filter_group_audience_state';
    $data[$type]['state']['field']['handler'] = 'og_handler_field_group_audience_state';
  }

  // Remove group audience - "state" and "created", as they are deprecated and
  // we have this data in the group membership entity.
  foreach (array('state', 'created') as $type) {
    unset($data['field_data_group_audience']['group_audience_'. $type]);
  }
}
