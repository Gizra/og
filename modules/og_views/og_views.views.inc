<?php
// $Id$

/**
 * Implementation of hook_views_handlers().
 */
function og_views_views_handlers() {
  return array(
    'info' => array(
      'path' => drupal_get_path('module', 'og_views') .'/includes',
    ),
    'handlers' => array(
      // filter
      'og_views_handler_filter_og_group_nid' => array(
        'parent' => 'views_handler_filter_many_to_one',
      ),
      'og_views_handler_filter_og_type_all' => array(
        'parent' => 'views_handler_filter',
      ),
      'og_views_handler_filter_og_type' => array(
        'parent' => 'views_handler_filter_node_type',
      ),
      'og_views_handler_filter_og_selective' => array(
        'parent' => 'views_handler_filter_in_operator',
      ),
      'og_views_handler_filter_og_picg' => array(
        'parent' => 'views_handler_filter',
      ),
      'og_views_handler_filter_og_is_admin' => array(
        'parent' => 'views_handler_filter_in_operator',
      ),
      // field
      'og_views_handler_field_og_post_count_new' => array(
        'parent' => 'views_handler_field_numeric',
      ),
      'og_views_handler_field_og_post_count' => array(
        'parent' => 'views_handler_field_numeric',
      ),
      'og_views_handler_field_og_member_count' => array(
        'parent' => 'views_handler_field_numeric',
      ),
      'og_views_handler_field_og_selective' => array(
        'parent' => 'views_handler_field',
      ),
      'og_views_handler_field_og_subscribe' => array(
        'parent' => 'views_handler_field',
      ),
      'og_views_handler_field_og_group_nids' => array(
        'parent' => 'views_handler_field_prerender_list',
      ),
      'og_views_handler_field_og_managelink' => array(
        'parent' => 'views_handler_field',
      ),
      'og_views_handler_field_og_is_active' => array(
        'parent' => 'views_handler_field',
      ),
      'og_views_handler_field_og_is_admin' => array(
        'parent' => 'views_handler_field',
      ),
      'og_views_handler_field_og_is_manager' => array(
        'parent' => 'views_handler_field',
      ),
      'og_views_handler_field_og_managelinkadmin' => array(
        'parent' => 'views_handler_field',
      ),
      'og_views_handler_field_og_managelinkmy' => array(
        'parent' => 'views_handler_field',
      ),
      'og_views_handler_field_og_uid_groups' => array(
        'parent' => 'views_handler_field_prerender_list',
      ),
      // argument
      'og_views_handler_argument_og_group_nid' => array(
        'parent' => 'views_handler_argument_numeric',
      ),
      'og_views_handler_argument_og_uid_nid' => array(
        'parent' => 'views_handler_argument_numeric',
      ),
      
    ),
  );
}

/**
 * Implementation of hook_views_plugins().
 */
function og_views_views_plugins() {
  return array(
    'argument validator' => array(
      'og_group_types' => array(
        'title' => t('Group nodes'),
        'handler' => 'og_views_plugin_argument_validate_og_group_types',
        'path' => drupal_get_path('module', 'og_views') . '/includes',
      ),
    ),
  );
}

/**
 * Implementation of hook_views_data().
 */
function og_views_views_data() {
  $data = array();
  $tables = array('og', 'og_ancestry', 'og_uid');
  foreach ($tables as $table) {
    $function = "og_views_data_$table";
    $data += $function();
  }
  
  return $data; 
}

/**
 * Implementation of hook_views_query_substitutions().
 */
function og_views_query_substitutions($view) {
  $groupnode = og_get_group_context();
  // return -1 instead of 0 to avoid matching all posts without a group
  return array('***CURRENT_GID***' => $groupnode ? $groupnode->nid : -1);
}


// ---------- Table OG

function og_views_data_og() {
  $data['og']['table']['group']  = t('Organic groups');

  $data['og']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );
  $data['og']['description'] = array(
    'title' => t('Group: Description'),
    'real field' => 'og_description',
    'field' => array(
      'handler' => 'views_handler_field_markup',
      'format' => FILTER_FORMAT_DEFAULT,
      'click sortable' => FALSE,
    ),
  );
  $data['og']['member_count'] = array(
    'title' => t('Group: Members count'),
    'field' => array(
      'handler' => 'og_views_handler_field_og_member_count',
      'click sortable' => TRUE,
      'help' => t('Number of members for a group. Excludes memberships which are pending approval.'),
    ),
  );
  $data['og']['post_count'] = array(
    'title' => t('Group: Post count'),
    'field' => array(
      'handler' => 'og_views_handler_field_og_post_count',
      'click sortable' => TRUE,
      'help' => t('Number of published posts in a group. Can be restricted by node type using the <em>Option</em> dropdown.'),
      'notafield' => TRUE,
     ),
  );
  $data['og']['post_count_new'] = array(
      'title' => t('Group: Post count *new*'),
      'field' => array(
        'handler' => 'og_views_handler_field_og_post_count_new',
        'click sortable' => TRUE,
        'help' => t('Number of new posts in a group for the current user.'),
      ),
  );
  $data['og']['selective'] = array(
    'title' => t('Group: Selective'),
    'help' => t('The group preference which determines how membership requests are managed (moderated, invite only, etc.).'),
    'real field' => 'og_selective',
    'field' => array(
      'handler' => 'og_views_handler_field_og_selective',
    ),
    'filter' => array(
      'handler' => 'og_views_handler_filter_og_selective',
    ),
  );
  $data['og']['private'] = array(
    'title' => t('Group: Private'),
    'help' => t('Is the group home page private or not.'),
    'real field' => 'og_private',
    'field' => array(
      'handler' => 'views_handler_field_boolean',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['og']['directory'] = array(
    'title' => t('Group: List in directory'),
    'help' => t('Admin specifies whether or not a group appears in the public listings.'),
    'real field' => 'og_directory',
    'field' => array(
      'real field' => 'og_directory',
      'handler' => 'views_handler_field_boolean',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['og']['subscribe'] = array(
    'title' => t('Group: Join link'),
    'help' => t('Displays a link for joining a group when a user is not already a member.'),
    'field' => array(
      'handler' => 'og_views_handler_field_og_subscribe',
    ),
  );
  if (module_exists('locale')) {
    $data['og']['language'] = array(
      'title' => t('Group: Language'),
      'help' => t('Displays the language selected for a given group.'),
      'real field' => 'og_language',
      'field' => array(
        'handler' => 'views_handler_field_node_language',
        'click sortable' => TRUE,
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_node_language',
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_node_language',
      ),
    );
  }
  
  // Pseudofield which actually operates on node.type
  $data['og']['type_groups'] = array(
    'title' => t('Group types'), // The item it appears as on the UI,
    'help' => t('The type of a group (for example, "blog entry", "forum post", "story", etc).'),
    'real field' => 'type',
    'filter' => array(
      'handler' => 'og_views_handler_filter_og_type',
    ),
  );
  $data['og']['type_groups_all'] = array(
    'title' => t('Group types (all)'), // The item it appears as on the UI,
    'help' => t('The type of a group (for example, "blog entry", "forum post", "story", etc).'),
    'real field' => 'type',
    'filter' => array(
      'handler' => 'og_views_handler_filter_og_type_all',
      'help' => t('Restrict to all content types that have been marked as groups.'),
    ),
  );
  
  return $data;
}

// ------------ Table 'og_ancestry'

function og_views_data_og_ancestry() {
  $data['og_ancestry']['table']['group']  = t('Organic groups');
  $data['og_ancestry']['table']['base'] = array(
    'field' => 'nid',
    'title' => t('Group posts'),
    'help' => t('Posts which are affiiated with a group.'),
  );
  $data['og_ancestry']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  $data['og_ancestry']['group_nid'] = array(
    'title' => t('Groups'),
    'help' => t('The groups for a post.'),
    'field' => array(
      'handler' => 'og_views_handler_field_og_group_nids',
    ),
    'relationship' => array(
      'title' => t('Group node (post)'),
      'help' => t("Bring in information about the group node based on a post's groups."),
      'base' => 'node',
      'field' => 'group_nid',
      'handler' => 'views_handler_relationship',
      'label' => t('Group node (post)'),
    ),
    'filter' => array(
      'handler' => 'og_views_handler_filter_og_group_nid',
      'numeric' => TRUE,
      'allow empty' => TRUE, // To select posts with no groups. Requires http://drupal.org/node/340824
      'help' => t('<strong>Posts</strong> are filtered for specific organic groups.'),
    ),
    'argument' => array(
      'name' => t('Post: in specified group (by number)'),
      'name field' => 'title', // the field to display in the summary.
      'validate type' => 'nid',
      'handler' => 'og_views_handler_argument_og_group_nid',
      'help' => t('<strong>Posts</strong> are filtered for specified organic groups. The page context (if Display is a page) will be set to the first listed group. That means that blocks and breadcrumbs (and theme and locale, if applicable) will be based upon the first specified node id.'),
    ),
  );
  $data['og_ancestry']['nid'] = array(
    'title' => t('Post: Nid'),
    'help' => t('The node ID of the node.'),
    'field' => array(
      'handler' => 'views_handler_field_node',
    ),    
  );
  $data['og_ancestry']['picg'] = array(
    'title' => t('OG: Posts in current group'),
    'help' => t('Posts in current group. Useful for blocks where determining context is hard. If page is not in any group context, no nodes are listed and thus a block would not appear.'),
    'filter' => array(
      'handler' => 'og_views_handler_filter_og_picg',
    ),
  );
  
  // TOODL: yes_empty field
  $data['og_ancestry']['is_public'] = array(
    'title' => t('Post: Public'),
    'help' => t('Is a given post public or private according to OG.'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  return $data;
}

// --------- Table og_uid

function og_views_data_og_uid() {
  $data['og_uid']['table']['group']  = t('Organic groups');
  $data['og_uid']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
    'users' => array(
      'left_field' => 'uid',
      'field' => 'uid',
    ),
  );
  $data['og_uid']['nid'] = array(
    'title' => t('Group'),
    'help' => t('Group that a member belongs to.'),
    'relationship' => array(
      'title' => t('Group node (member)'),
      'help' => t("Bring in information about the group node based on a user's membership."),
      'base' => 'node',
      'field' => 'nid',
      'handler' => 'views_handler_relationship',
      'label' => t('Group node (member)'),
    ),
    'argument' => array(
      'title' => t('Group node'),
      'handler' => 'og_views_handler_argument_og_uid_nid',
      'help' => t('<strong>Members</strong> are filtered for a specific group.'),
    ),
  ); 
  $data['og_uid']['groups'] = array(
    'title' => t('User\'s groups'),
    'help' => t('Groups that a member belongs to.'),
    'field' => array(
      'field' => 'uid',
      'handler' => 'og_views_handler_field_og_uid_groups',
      'help' => t("OG: List of user's groups"),
    ),
  );
  $data['og_uid']['uid'] = array(
    'title' => t('Group member'),
    // 'help' => t('foo')
    'filter' => array(
      'handler' => 'views_handler_filter_user_current',
      'help' => t("OG: Group in current user's groups"),
    ),
   'argument' => array(
     'title' => t('Member of a group'),
     'handler' => 'views_handler_argument_numeric',
     'help' => t("OG: Group in specified user's groups. Note that OG usually considers a user's groups to be private information. You may want to add access control to your View or validation to this argument."),
   ),
  );
  $data['og_uid']['managelinkadmin'] = array(
    'title' => t('OG: Admin manage link'),
    'help' => t('A link to the <em>Admin create or remove</em> page.'),
    'field' => array(
      'handler' => 'og_views_handler_field_og_managelinkadmin',
      'click sortable' => FALSE,
    ),
  );
  $data['og_uid']['managelinkmy'] = array(
    'title' => t('OG: Edit membership link'),
    'help' => t('A link to the <em>My membership</em> page.'),
    'field' => array(
      'handler' => 'og_views_handler_field_og_managelinkmy',
      'click sortable' => FALSE,
    ),
  );
  $data['og_uid']['managelink'] = array(
    'title' => t('OG: Approve/Deny/Remove membership link'),
    'help' => t('A link to approve/deny/remove a group member.'),
    'field' => array(
      'handler' => 'og_views_handler_field_og_managelink',
      'click sortable' => FALSE,
    ),
  );
  $data['og_uid']['is_admin'] = array(
    'title' => t('OG: Is member an admin in a group'),
    'help' => t('Add <em>admin</em> text if user is the group manager.'),
    'field' => array(
      'handler' => 'og_views_handler_field_og_is_admin',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'og_views_handler_filter_og_is_admin',
    ),
    'sort' => array(
       'handler' => 'views_handler_sort',
    ),
  );
  $data['og_uid']['is_active'] = array(
    'title' => t('OG: Is membership approved'),
    'help' => t("Add <em>approval needed</em> user if user's membership request is pending."),
    'field' => array(
      'handler' => 'og_views_handler_field_og_is_active',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
       'handler' => 'views_handler_sort',
    ),
  );
  $data['og_uid']['is_manager'] = array(
    'title' => t('OG: Is the group manager'),
    'help' => t('Add <em>manager</em> text if user is the group manager.'),
    'field' => array(
      'field' => 'uid',
      'handler' => 'og_views_handler_field_og_is_manager',
      'click sortable' => FALSE,
    ),
  );
  $data['og_uid']['created'] = array(
    'title' => t('OG: Membership create date'),
    'help' => t('The date when the membership was created.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
    'sort' => array(
       'handler' => 'views_handler_sort_date',
    ),
  );
  $data['og_uid']['changed'] = array(
    'title' => t('OG: Membership last updated date'),
    'help' => t('The date when the membership was last updated.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
    'sort' => array(
       'handler' => 'views_handler_sort_date',
    ),
  );
    
  return $data;
}

function og_views_set_breadcrumb($nid) {
  $node = node_load((int)$nid);
  $bc = array(
    '<front>' => t('Home'),
    "node/$nid" => check_plain($node->title),
  );
  return $bc;
}
