<?php
// $Id$
/**
 * Implementation of the hook_views_tables()
 */
 
function og_views_tables() {
  $types = variable_get('og_node_types', array('og'));
  foreach ($types as $type) {
    $type_options[$type] = node_get_types('name', $type);
  }
  
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
          'name' => 'Og: Group: Description',
        ),
        'website' => array(
          'name' => 'Og: Group: Website',
          'handler' => 'og_handler_field_website',
        ),
        'count' => array(
          'name' => 'Og: Group: Subscriber Count',
          'handler' => 'og_handler_field_count',
          'sortable' => FALSE,
          'notafield' => TRUE,
        ),
        'selective' => array(
          'name' => 'Og: Group: Selective',
        ),
        'subscribe' => array(
          'name' => 'Og: Group: Subscribe Link',
          'handler' => 'og_handler_field_subscribe',
          'addlfields' => array('selective'),
          'sortable' => FALSE,
          'notafield' => TRUE,
        ),
        'notification' => array(
          'name' => 'Og: Group: Notification',
          'handler' => 'og_handler_field_yesempty',
          'help' => t('Displays <em>yes</em> if group automatically sends email notifications to subscribers'),
        ),
        'language' => array(
          'name' => 'Og: Group: Language',
          'help' => t('Displays the language selected for a given group'),
        ),
        
      ),
      'filters' => array(
        'directory' => array(
          'name' => "Og: Group: List in directory",
          'operator' => array('=' => 'Equals'),
          'list' => 'views_handler_operator_yesno',
          'list-type' => 'select',
          'help' => t('Admin specifies whether or not a group appears in the public listings.'),
        )
      ),
    ),
    'og_ancestry' => array(
      'name' => 'og_ancestry',
      'join' => array(
        'left' => array(
          'table' => 'node',
          'field' => 'nid',
        ),
        'right' => array(
          'field' => 'nid',
        ),
      ),
      'filters' => array(
        'gid' => array(
          'name' => 'Og: Post in specified group',
          'operator' => 'views_handler_operator_eqneq',
          'field' => 'group_nid',
          'help' => t('Filter for a given organic group. <em>Value</em> should be a numeric nid.'),   
        ),
        'gtype' => array(
          'name' => 'Og: Post in specified group type',
          'list' => variable_get('og_node_types', array('og')),
          'list-type' => 'list',
          'operator' => 'views_handler_operator_or',
          'value-type' => 'array',
          'field' => 'group_nid',
          'handler' => 'og_handler_filter_post_og_type',
          'help' => t('Filter for a given organic group type.'),
        ),
      ),    
      'fields' => array(  
        'is_public' => array(
          'name' => 'Og: Public',
          'handler' => 'og_handler_field_yesempty',
          'help' => t('Displays <em>yes</em> if post is public according to OG.'),
        )  
      ),
    ),
    'og_node_data' => array(
      'name' => 'node',
      'join' => array(
        'left' => array(
          'table' => 'og_ancestry',
          'field' => 'group_nid',
          ),
        'right' => array(
          'field' => 'nid',
        ),
      ),
      'fields' => array(
        'title' => array(
          'name' => t('Og: Group names'),
          'handler' => 'og_handler_field_nodelink',
          'notafield' => TRUE,
          'help' => t('Display links to any groups that are affiliated with a post.'),
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
          'name' => 'Og: Is subscriber an admin in a group',
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
          'table' => 'og_node_data',
          'field' => 'nid',
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
    // pseudotable for adding filter on node table and eventually more
    'og_views' => array(
      'name' => 'node',
      'join' => array(
        'left' => array(
          'table' => 'node',
          'field' => 'nid',
          ),
        'right' => array(
          'field' => 'nid',
        )
      ),
      // here's a filter that is limited to og content types to be used for exposed filters that should not contain other values
     'filters' => array(
        'og_type_selector' => array(
          'field' => 'type_selector',
          'name' => t('OG: type (selector)'),
          'list' => $type_options,
          'list-type' => 'list',
          'operator' => 'views_handler_operator_or',
          'value-type' => 'array',
          'help' => t('A node type selector that is limited to og-enabled node types. Use as exposed filter that only contains og node types.'),
        ),
        'og_type' => array(
          'field' => 'type',
          'name' => t('OG: type'),
          'operator' => array('=' => t('Is')),
          'list' => array('a group node'),
          'list-type' => 'select',
          'handler' => 'og_handler_filter_og_type',
          'help' => t('Restrict to node types which have been designated behave as groups.'),
        ),
      ),
    ),
  );  
}

function og_views_arguments() {
  $args = array(
    'gid' => array(
      'name' => t("Og: Group nid"), 
      'handler' => 'og_handler_argument_gid',
      'help' => t('Filter for a given organic group'),
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
      // TODO: maybe there is a nicer syntax for this?
      $query->ensure_table('og_ancestry');
      $query->add_where('og_ancestry.group_nid = %d', $arg);
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
  $view->name = 'og_mytracker';
  $view->description = t('Shows all activity in subscribed groups.');
  $view->access = array('2');
  $view->view_args_php = '';
  $view->page = TRUE;
  $view->page_title = t('Recent posts in my groups');
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
  $view->menu_title = t('My recent');
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
  $view->description = t('Shows unread posts in subscribed groups.');
  $view->access = array (
  0 => '2',
);
  $view->view_args_php = '';
  $view->page = TRUE;
  $view->page_title = t('Unread posts in my groups');
  $view->page_header = '';
  $view->page_header_format = '1';
  $view->page_footer = '';
  $view->page_footer_format = '1';
  $view->page_empty = t('There are no new posts in your subscribed groups.');
  $view->page_empty_format = '1';
  $view->page_type = 'table';
  $view->url = 'group/myunread';
  $view->use_pager = TRUE;
  $view->nodes_per_page = '25';
  $view->menu = TRUE;
  $view->menu_title = t('My unread');
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
  
  
  // Group home page: river of news
  $view = new stdClass();
  $view->name = 'og_ghp_ron';
  $view->description = 'OG: Group home page - River of news. Default';
  $view->page_type = 'teaser';
  $view->access = array ();
  $view->view_args_php = '';
  $view->sort = array (
    array (
      'tablename' => 'node',
      'field' => 'sticky',
      'sortorder' => 'DESC',
      'options' => '',
    ),
    array (
      'tablename' => 'node',
      'field' => 'created',
      'sortorder' => 'DESC',
      'options' => '',
    ),
  );
  $view->argument = array (
    array (
      'type' => 'gid',
      'argdefault' => '1',
      'title' => '%1',
      'options' => '',
      'wildcard' => '',
      'wildcard_substitution' => '',
    ),
    array (
      'type' => 'rss_feed',
      'argdefault' => '2',
      'title' => '',
      'options' => '',
      'wildcard' => '',
      'wildcard_substitution' => '',
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
  );
  $view->exposed_filter = array ();
  $view->requires = array(node);
  $views[$view->name] = $view;
  
  // og/my
  $view = new stdClass();
  $view->name = 'og_my';
  $view->description = 'List user\'s subscribed groups and provide link to manage subscription';
  $view->access = array (
  0 => '2',
);
  $view->view_args_php = '';
  $view->page = TRUE;
  $view->page_title = 'My groups';
  $view->page_header = 'You may edit your all your email subscription using this convenient page. Also, you might be interested in an <a href="/og/opml" rel="nofollow">OPML feed containing feeds from all your subscribed groups</a>.';
  $view->page_header_format = '1';
  $view->page_footer = '<?php

  $url = url("og/opml");
  return theme(\'opml_icon\', $url);

?>';
  $view->page_footer_format = '2';
  $view->page_empty = 'No subscribed groups';
  $view->page_empty_format = '1';
  $view->page_type = 'table';
  $view->url = 'og/my';
  $view->use_pager = TRUE;
  $view->nodes_per_page = '50';
  $view->menu = TRUE;
  $view->menu_title = '';
  $view->menu_tab = TRUE;
  $view->menu_tab_default = FALSE;
  $view->menu_tab_weight = '0';
  $view->block = FALSE;
  $view->sort = array (
    array (
      'tablename' => 'node',
      'field' => 'title',
      'sortorder' => 'ASC',
      'options' => '',
    ),
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
      'tablename' => 'node',
      'field' => 'title',
      'label' => 'Title',
      'handler' => 'views_handler_field_nodelink',
      'options' => 'link',
    ),
    array (
      'tablename' => 'users',
      'field' => 'name',
      'label' => 'Manager',
    ),
    array (
      'tablename' => 'og',
      'field' => 'count',
      'label' => 'Subscribers',
    ),
    array (
      'tablename' => 'og_uid',
      'field' => 'mail_type',
      'label' => 'Email',
    ),
    array (
      'tablename' => 'og_uid',
      'field' => 'managelink',
      'label' => 'Manage',
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
      'tablename' => 'og_uid',
      'field' => 'currentuidsimple',
      'operator' => '=',
      'options' => '',
      'value' => '***CURRENT_USER***',
    ),
    array (
      'tablename' => 'og_views',
      'field' => 'og_type',
      'operator' => '=',
      'options' => '',
      'value' => '\'0\'',
    ),
  );
  $view->exposed_filter = array (
  );
  $view->requires = array(node, users, og, og_uid, og_views);
  $views[$view->name] = $view;
  
  // og view
  $view = new stdClass();
  $view->name = 'og';
  $view->description = 'groups directory';
  $view->access = array (
);
  $view->view_args_php = '';
  $view->page = TRUE;
  $view->page_title = 'Groups directory';
  $view->page_header = '';
  $view->page_header_format = '2';
  $view->page_footer = '';
  $view->page_footer_format = '1';
  $view->page_empty = 'No groups';
  $view->page_empty_format = '1';
  $view->page_type = 'table';
  $view->url = 'og/all';
  $view->use_pager = TRUE;
  $view->nodes_per_page = '90';
  $view->menu = TRUE;
  $view->menu_title = 'Groups';
  $view->menu_tab = TRUE;
  $view->menu_tab_default = TRUE;
  $view->menu_tab_weight = '-5';
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
      'tablename' => 'node',
      'field' => 'title',
      'label' => 'Title',
      'handler' => 'views_handler_field_nodelink',
      'sortable' => '1',
      'options' => 'link',
    ),
    array (
      'tablename' => 'users',
      'field' => 'name',
      'label' => 'Manager',
    ),
    array (
      'tablename' => 'og',
      'field' => 'count',
      'label' => 'Subscribers',
    ),
    array (
      'tablename' => 'og',
      'field' => 'description',
      'label' => 'Description',
    ),
    array (
      'tablename' => 'og',
      'field' => 'subscribe',
      'label' => 'Subscribe',
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
      'tablename' => 'og',
      'field' => 'directory',
      'operator' => '=',
      'options' => '',
      'value' => '1',
    ),
    array (
      'tablename' => 'node',
      'field' => 'title',
      'operator' => 'contains',
      'options' => '',
      'value' => '\'\'',
    ),
    array (
      'tablename' => 'og_views',
      'field' => 'og_type',
      'operator' => '=',
      'options' => '',
      'value' => '0',
    ),
  );
  $view->exposed_filter = array (
    array (
      'tablename' => 'node',
      'field' => 'title',
      'label' => 'Title',
      'optional' => '1',
      'is_default' => '0',
      'operator' => '1',
      'single' => '0',
    ),
  );
  $view->requires = array(node, users, og, og_views);
  $views[$view->name] = $view;
  
  // og_tracker
  $view = new stdClass();
  $view->name = 'og_tracker';
  $view->description = 'Shows active posts across the whole site';
  $view->access = array (
);
  $view->view_args_php = '';
  $view->page = TRUE;
  $view->page_title = 'Recent posts across whole site';
  $view->page_header = '';
  $view->page_header_format = '1';
  $view->page_footer = '';
  $view->page_footer_format = '1';
  $view->page_empty = '';
  $view->page_empty_format = '1';
  $view->page_type = 'table';
  $view->url = 'group/tracker';
  $view->use_pager = TRUE;
  $view->nodes_per_page = '25';
  $view->menu = TRUE;
  $view->menu_title = 'Recent posts';
  $view->menu_tab = TRUE;
  $view->menu_tab_default = FALSE;
  $view->menu_tab_weight = '5';
  $view->sort = array (
    array (
      'tablename' => 'node_comment_statistics',
      'field' => 'last_comment_timestamp',
      'sortorder' => 'DESC',
      'options' => 'normal',
    ),
  );
  $view->argument = array (
    array (
      'type' => 'uid',
      'argdefault' => '2',
      'title' => 'recent posts for %1',
      'options' => '',
      'wildcard' => '',
      'wildcard_substitution' => '',
    ),
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
      'tablename' => 'node',
      'field' => 'type',
      'label' => 'Type',
    ),
    array (
      'tablename' => 'node',
      'field' => 'title',
      'label' => 'Title',
      'handler' => 'views_handler_field_nodelink_with_mark',
      'options' => 'link',
    ),
    array (
      'tablename' => 'users',
      'field' => 'name',
      'label' => 'Author',
    ),
    array (
      'tablename' => 'node_comment_statistics',
      'field' => 'comment_count',
      'label' => 'Replies',
      'handler' => 'views_handler_comments_with_new',
    ),
    array (
      'tablename' => 'og_node_data',
      'field' => 'title',
      'label' => 'Groups',
    ),
    array (
      'tablename' => 'node_comment_statistics',
      'field' => 'last_comment_timestamp',
      'label' => 'Last Post',
      'handler' => 'views_handler_field_since',
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
  );
  $view->exposed_filter = array (
  );
  $view->requires = array(node_comment_statistics, node, users, og_node_data);
  $views[$view->name] = $view;
  
  // in group search - og_search
  $view = new stdClass();
  $view->name = 'og_search';
  $view->description = 'Search within a particular group';
  $view->access = array (
);
  $view->view_args_php = '';
  $view->page = TRUE;
  $view->page_title = 'Search';
  $view->page_header = '';
  $view->page_header_format = '1';
  $view->page_footer = '';
  $view->page_footer_format = '1';
  $view->page_empty = 'No posts found.';
  $view->page_empty_format = '1';
  $view->page_type = 'teaser';
  $view->url = 'og/search';
  $view->use_pager = TRUE;
  $view->nodes_per_page = '50';
  $view->sort = array (
    array (
      'tablename' => 'node',
      'field' => 'created',
      'sortorder' => 'DESC',
      'options' => 'normal',
    ),
  );
  $view->argument = array (
    array (
      'type' => 'gid',
      'argdefault' => '1',
      'title' => 'Search results for %1',
      'options' => '',
      'wildcard' => '',
      'wildcard_substitution' => '',
    ),
  );
  $view->field = array (
  );
  $view->filter = array (
    array (
      'tablename' => 'temp_search_results',
      'field' => 'word',
      'operator' => '=',
      'options' => '',
      'value' => '',
    ),
    array (
      'tablename' => 'node',
      'field' => 'status',
      'operator' => '=',
      'options' => '',
      'value' => '1',
    ),
  );
  $view->exposed_filter = array (
    array (
      'tablename' => 'temp_search_results',
      'field' => 'word',
      'label' => 'Enter keyword',
      'optional' => '1',
      'is_default' => '0',
      'operator' => '1',
      'single' => '1',
    ),
  );
  $view->requires = array(node, temp_search_results);
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
        return l(t('subscribe'), "og/subscribe/$data->nid", array('rel' => 'nofollow'), drupal_get_destination());  
    }
  }
}

function og_handler_field_nodelink($fieldinfo, $fielddata, $value, $data) {
  // we have to query to get all the groups into a single element
  $result = og_get_node_groups_result($data->nid);
  while ($row = db_fetch_object($result)) {
    $links[] = l($row->title, "node/$row->group_nid");
  }
  if ($links) {
    return implode(', ', $links);
  }
}

function og_handler_field_managelink($fieldinfo, $fielddata, $value, $data) {
  return l(t('my subscription'), "og/manage/$data->nid", NULL, drupal_get_destination());
}

function og_handler_field_yesempty($fieldinfo, $fielddata, $value, $data) {
  return $value ? t('yes') : '';
}

function og_handler_filter_og_type($op, $filter, $filterinfo, &$query) {
  $types = variable_get('og_node_types', array('og'));
  $in = implode(', ', array_fill(0, count($types), "'%s'"));
  $query->add_where("node.type IN ($in)", $types);
}

function og_handler_filter_post_og_type($op, $filter, $filterinfo, &$query) {
  $query->ensure_table('og_ancestry');
  $query->add_table('og_node_data');
  $types = $filter['value'];
  $placeholders = array_fill(0, count($types), "'%s'");
  $query->add_where("og_node_data.type IN (". implode(", ", $placeholders) .")", $types);
}