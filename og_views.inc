<?php
// $Id$
/**
 * Implementation of the hook_views_tables()
 */

function og_views_tables() {
  return array(
    'og' => array(
      'name' => 'og',
      'join' => array(
        'left' => array(
          'table' => 'node',
          'field' => 'nid'
        ),
        'right' => array(
          'field' => 'nid'
        ),
      ),
      'fields' => array(
        'description' => array(
          'name' => 'Og: Group Description',
        ),
        'website' => array(
          'name' => 'Og: Group Website',
          'handler' => 'og_handler_field_website',
        ),
        'count' => array(
          'name' => 'Og: Subscriber Count',
          'handler' => 'og_handler_field_count',
          'sortable' => FALSE,
          'notafield' => TRUE,
        ),
        'selective' => array(
          'name' => 'Og: Selective',
        ),
        'subscribe' => array(
          'name' => 'Og: Subscribe Link',
          'handler' => 'og_handler_field_subscribe',
          'addlfields' => array('selective'),
          'sortable' => FALSE,
          'notafield' => TRUE,
        ),
      ),
      'filters' => array(
        'directory' => array(
          'name' => "Og: List in directory",
          'operator' => array('=' => 'Equals'),
          'list' => 'views_handler_operator_yesno',
          'list-type' => 'select',
          'help' => t('Admin specifies whether or not a group appears in the public listings.'),
        )
      )
    ),
    'og_node' => array(
      'name' => 'node_access',
      'join' => array(
        'left' => array(
          'table' => 'node',
          'field' => 'nid',
          ),
        'right' => array(
          'field' => 'nid',
          ),
         'extra' => array(
            'realm' => 'og_subscriber',
            'gid != node.nid' => NULL,
          ),
        ),
      ),
    'og_node_data' => array(
      'name' => 'node',
      'join' => array(
        'left' => array(
          'table' => 'og_node',
          'field' => 'gid',
          ),
        'right' => array(
          'field' => 'nid',
        ),
      ),
      'fields' => array(
        'title' => array(
          'name' => t('Og: Group Name'),
          'handler' => 'og_handler_field_nodelink',
          'sortable' => true,
          'addlfields' => array('nid'),
          'help' => t('Display the title of the first group a node belongs to.'),
        ),
      ),
    ),
    'og_uid' => array(
      'name' => 'og_uid',
      'join' => array(
        'left' => array(
          'table' => 'node',
          'field' => 'nid',
        ),
        'right' => array(
          'field' => 'nid',
        ),
        'extra' => array(
          'is_active' => 1,
        ),
      ),
      'fields' => array(
        'mail_type' => array(
          'name' => t('Og: Subscription email'),
          'handler' => 'og_handler_field_yesempty',
          'sortable' => false,
          'help' => t('Does subscriber receive email notifications for a group.'),
        ),
        'managelink' => array(
          'name' => 'Og: Manage subscription link',
          'handler' => 'og_handler_field_managelink',
          'sortable' => FALSE,
          'notafield' => TRUE,
        ),
        'is_admin' => array(
          'name' => 'Og: is subscriber an admin in a group',
          'handler' => 'og_handler_field_yesempty',
          'sortable' => FALSE,
        ),
      ),
      'filters' => array(
        'currentuidsimple' => array(
          'field' => 'uid',
          'name' => t('Og: Group in User Subbed Groups'),
          'operator' => 'views_handler_operator_eqneq',
          'list' => 'views_handler_filter_usercurrent',
          'list-type' => 'select',
          'help' => t('Groups are filtered to where current user is a member .'),
        ),
      ),            
    ),
    'og_uid_node' => array(
      'name' => 'og_uid',
      'join' => array(
        'left' => array(
          'table' => 'og_node',
          'field' => 'gid',
        ),
        'right' => array(
          'field' => 'nid',
        ),
        'extra' => array(
          'is_active' => 1,
        ),
      ),
      'filters' => array(
        'currentuid' => array(
          'field' => 'uid',
          'name' => t('Og: Post in User Subbed Groups'),
          'operator' => 'views_handler_operator_eqneq',
          'list' => 'views_handler_filter_usercurrent',
          'list-type' => 'select',
          'help' => t('Posts are filtered to groups that current user is a member of.'),
        ),
      ),            
    ),
    'node_access' => array(
      'name' => 'node_access',
      'join' => array(
        'left' => array(
          'table' => 'node',
          'field' => 'nid',
          ),
        'right' => array(
          'table' => 'node_access',
          'field' => 'nid',
                   )
        )
    )
  );
}

function og_views_arguments() {
  $args = array(
    'gid' => array(
      'name' => t("Og: Group nid"), 
      'handler' => 'og_handler_argument_gid',
      'help' => t('This argument filters for a given organic group'),
      )
    );
  return $args;
}

/*
 * Custom argument for filtering by an og.
 */
function og_handler_argument_gid($op, &$query, $argtype, $arg = '') {
  switch ($op) {
    case 'filter':
      $prefix = 'na';
      // we add this JOIN for node admins and when node access is disabled because node_rewrite_sql() adds it for everyone else
      if (user_access('administer nodes') || node_access_view_all_nodes()) {
        $query->ensure_table('node_access');
        $prefix = 'node_access';
        // $query->set_count_field('nid'); // views doesn't do a distinct unless a field is specified here
      }
      $where[] = "($prefix.gid = %d AND $prefix.realm = 'og_subscriber')";
      $where[] = "($prefix.grant_view = %d AND $prefix.realm = 'og_public')";
      $where = implode(' OR ', $where);
      $query->add_where($where, $arg, $arg);
      $query->add_where("$prefix.nid IS NOT NULL"); // counteract the fact that views doesn't do inner joins yet
      break;
    case 'title':
      return db_result(db_query_range('SELECT title FROM {node} WHERE nid = %d', $query, 0, 1));;
  }
}

/**
 * Provide a default view for getting new posts in a group
 */
function og_views_default_views() {
  $view = new stdClass();
  $view->name = 'og_tracker';
  $view->description = t('Shows all activity in subscribed groups.');
  $view->access = array('2');
  $view->view_args_php = '';
  $view->page = TRUE;
  $view->page_title = t('recent posts in my groups');
  $view->page_header = '';
  $view->page_header_format = '1';
  $view->page_footer = '';
  $view->page_footer_format = '1';
  $view->page_empty = t('There are no posts in your subscribed groups.');
  $view->page_empty_format = '1';
  $view->page_type = 'table';
  $view->url = 'group/mytracker';
  $view->use_pager = TRUE;
  $view->nodes_per_page = '25';
  $view->menu = TRUE;
  $view->menu_title = t('my recent');
  $view->menu_tab = TRUE;
  $view->menu_tab_default = FALSE;
  $view->menu_weight = '';
  $view->sort = array (
  );
  $view->argument = array (
    array (
      'type' => 'rss_feed',
      'argdefault' => '2',
      'title' => '',
      'options' => '',
      'wildcard' => '',
      'wildcard_substitution' => '',
    ),
  );  
  $view->field = array (
    array (
      'tablename' => 'og_node_data',
      'field' => 'title',
      'label' => t('Group'),
      'sortable' => '1',
    ),
    array (
      'tablename' => 'node',
      'field' => 'type',
      'label' => t('Type'),
      'sortable' => '1',
    ),
    array (
      'tablename' => 'node',
      'field' => 'title',
      'label' => t('Title'),
      'handler' => 'views_handler_field_nodelink_with_mark',
      'sortable' => '1',
    ),
    array (
      'tablename' => 'users',
      'field' => 'name',
      'label' => t('Author'),
      'sortable' => '1',
    ),
    array (
      'tablename' => 'node_comment_statistics',
      'field' => 'comment_count',
      'label' => t('Replies'),
      'handler' => 'views_handler_comments_with_new',
      'sortable' => '1',
    ),
    array (
      'tablename' => 'node_comment_statistics',
      'field' => 'last_comment_timestamp',
      'label' => t('Last Post'),
      'handler' => 'views_handler_field_since',
      'sortable' => '1',
      'defaultsort' => 'DESC',
    ),
  );
  $view->filter = array (
    array (
      'tablename' => 'node',
      'field' => 'status',
      'operator' => '=',
      'options' => '',
      'value' => '1',
    ),
    array (
      'tablename' => 'og_uid_node',
      'field' => 'currentuid',
      'operator' => '=',
      'options' => '',
      'value' => '***CURRENT_USER***',
    ),
//    array (
//      'tablename' => 'node',
//      'field' => 'distinct',
//      'operator' => '=',
//      'options' => '',
//      'value' => array(),
//    ),
  );
  $view->requires = array(og_node_data, node, users, node_comment_statistics, og_uid_node);
  $views[$view->name] = $view;

// OG_UNREAD VIEW
  $view = new stdClass();
  $view->name = 'og_unread';
  $view->description = 'Shows unread posts in subscribed groups.';
  $view->access = array (
  0 => '2',
);
  $view->view_args_php = '';
  $view->page = TRUE;
  $view->page_title = 'unread posts in my groups';
  $view->page_header = '';
  $view->page_header_format = '1';
  $view->page_footer = '';
  $view->page_footer_format = '1';
  $view->page_empty = 'There are no new posts in your subscribed groups.';
  $view->page_empty_format = '1';
  $view->page_type = 'table';
  $view->url = 'group/myunread';
  $view->use_pager = TRUE;
  $view->nodes_per_page = '25';
  $view->menu = TRUE;
  $view->menu_title = 'my unread';
  $view->menu_tab = TRUE;
  $view->menu_tab_default = TRUE;
  $view->menu_weight = '';
  $view->sort = array (
  );
  $view->argument = array (
    array (
      'type' => 'rss_feed',
      'argdefault' => '2',
      'title' => '',
      'options' => '',
      'wildcard' => '',
      'wildcard_substitution' => '',
    ),
  );
  $view->field = array (
    array (
      'tablename' => 'og_node_data',
      'field' => 'title',
      'label' => 'Group',
      'sortable' => '1',
    ),
    array (
      'tablename' => 'node',
      'field' => 'type',
      'label' => 'Type',
      'sortable' => '1',
    ),
    array (
      'tablename' => 'node',
      'field' => 'title',
      'label' => 'Title',
      'handler' => 'views_handler_field_nodelink_with_mark',
      'sortable' => '1',
    ),
    array (
      'tablename' => 'users',
      'field' => 'name',
      'label' => 'Author',
      'sortable' => '1',
    ),
    array (
      'tablename' => 'node_comment_statistics',
      'field' => 'comment_count',
      'label' => 'Replies',
      'handler' => 'views_handler_comments_with_new',
      'sortable' => '1',
    ),
    array (
      'tablename' => 'node_comment_statistics',
      'field' => 'last_comment_timestamp',
      'label' => 'Last Post',
      'handler' => 'views_handler_field_since',
      'sortable' => '1',
      'defaultsort' => 'DESC',
    ),
  );
  $view->filter = array (
    array (
      'tablename' => 'node',
      'field' => 'status',
      'operator' => '=',
      'options' => '',
      'value' => '1',
    ),
    array (
      'tablename' => 'og_uid_node',
      'field' => 'currentuid',
      'operator' => '=',
      'options' => '',
      'value' => '***CURRENT_USER***',
    ),
    array (
      'tablename' => 'history',
      'field' => 'timestamp',
      'operator' => '0',
      'options' => '',
      'value' => array (),
    ),
  );
  $view->exposed_filter = array();
  $view->requires = array(og_node_data, node, users, node_comment_statistics, og_uid_node, history);
  $views[$view->name] = $view;

  return $views;
}


/**
 * Views handler to display the number subscriptions
 */
function og_handler_field_count($fieldinfo, $fielddata, $value, $data) {
  global $user;

  $cnt = db_num_rows(db_query(og_list_users_sql(), $data->nid));
  return in_array($data->nid, array_keys($user->og_groups)) ? l($cnt, "og/users/$data->nid") : $cnt;
}

/**
 * Views handler to display the external website field
 */
function og_handler_field_website($fieldinfo, $fielddata, $value, $data) {
  if ($value) {
    return l(t('external website'), $value);
  }
}

/**
 * Views handler to display the subscribe link
 */
function og_handler_field_subscribe($fieldinfo, $fielddata, $value, $data) {
  global $user;
  if (!in_array($data->nid, array_keys($user->og_groups))) {
    switch ($data->og_selective) {
      case OG_CLOSED:
        return '<em>'. t('Closed'). '</em>';
      case OG_INVITE_ONLY:
        return '<em>'. t('Invite only'). '</em>';
      default:
        return l(t('subscribe'), "og/subscribe/$data->nid", array(), drupal_get_destination());  
    }
  }
}

function og_handler_field_nodelink($fieldinfo, $fielddata, $value, $data) {
  return l($value, "node/$data->og_node_data_nid");
}

function og_handler_field_managelink($fieldinfo, $fielddata, $value, $data) {
  return l(t('my subscription'), "og/manage/$data->nid");
}

function og_handler_field_yesempty($fieldinfo, $fielddata, $value, $data) {
  return $value ? t('yes') : '';
}