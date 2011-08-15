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

    $data['og']['etid'] = array(
      'title' => t('Entity id'),
      'help' => t('The numeric id of the entity.'),
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

    $data['field_data_group_audience']['user_roles'] = array(
      'group' => t('Group'),
      'title' => t('Group user roles'),
      'help' => t('Show all the roles a user belongs to in a group.'),
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

    $data['og_membership']['etid'] = array(
      'title' => t('Entity id'),
      'help' => t('The numeric id of the entity.'),
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

    return $data;
  }
}

/**
 * Implements hook_views_data_alter().
 */
function og_views_data_alter(&$data) {
  // TODO: This should be handled by entity-metadata.
  foreach (entity_get_info() as $entity_type => $info) {
    if (!empty($info['fieldable'])) {
      // Group relationship.
      if ($entity_type != 'group') {
        $title = t('@entity group', array('@entity' => ucfirst($info['label'])));
        $help = t('Add information on groups that belong to the @entity entity.', array('@entity' => ucfirst($info['label'])));
        $data[$info['base table']]['og_rel'] = array(
          'group' => t('Group'),
          'title' => $title,
          'help' => $help,
          'relationship' => array(
            // Pass the entity to the handler.
            'entity' => $entity_type,
            'handler' => 'og_handler_relationship',
            'label' => t('group'),
            'base' => 'og',
            'base field' => 'etid',
            'relationship field' => $info['entity keys']['id'],
          ),
        );
      }

      // Group membership relationship.
      if ($entity_type != 'og_membership') {
        $title = t('@entity group membership', array('@entity' => ucfirst($info['label'])));
        $help = t('Add information on groups membership that are associated with the @entity entity.', array('@entity' => ucfirst($info['label'])));
        $data[$info['base table']]['og_membership_rel'] = array(
          'group' => t('Group membership'),
          'title' => $title,
          'help' => $help,
          'relationship' => array(
            // Pass the entity to the handler.
            'entity' => $entity_type,
            'handler' => 'og_handler_relationship',
            'label' => t('group membership'),
            'base' => 'og_membership',
            'base field' => 'etid',
            'relationship field' => $info['entity keys']['id'],
          ),
        );
      }
    }
  }

  // Override group audience - "gid" (group ID) field argument.
  $data['og_membership']['group']['argument']['handler'] = 'og_handler_argument_group_audience_gid';

  // TODO: Remove field-data-group-audience completely form Views and use
  // group membership entity instead.
  unset($data['field_data_group_audience']['group_audience_gid']['argument']);
  unset($data['field_data_group_audience']['group_audience_gid']['field']);

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

/**
 * Implements hook_views_plugins
 */
function og_views_plugins() {
  return array(
    'argument validator' => array(
      'og' => array(
        'title' => t('Organic groups group'),
        'handler' => 'og_plugin_argument_validate_group',
        'path' => drupal_get_path('module', 'og') . '/includes/views',
      ),
    ),
  );
}