<?php
// $Id$

function og_views_data() {
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
    'field' => array(
      'handler' => 'views_handler_field_markup',
      'format' => FILTER_FORMAT_DEFAULT,
      'click sortable' => FALSE,
    ),
  );
  $data['og']['member_count'] = array(
    'title' => t('Group: Members Count'),
    'field' => array(
      'handler' => 'views_handler_field_og_member_count',
      'click sortable' => TRUE,
      'help' => t('Number of members for a group. Excludes memberships which are pending approval.'),
    ),
  );
  $data['og']['post_count'] = array(
    'title' => t('Group: Post Count'),
    'field' => array(
      'handler' => 'views_handler_field_og_post_count',
      'click sortable' => TRUE,
      'help' => t('Number of published posts in a group. Can be restricted by node type using the <em>Option</em> dropdown.'),
      'notafield' => TRUE,
     ),
  );
  $data['og']['post_count_new'] = array(
      'title' => t('Group: Post Count *New*'),
      'field' => array(
        'handler' => 'views_handler_field_og_post_count_new',
        'click sortable' => TRUE,
        'help' => t('Number of new posts in a group for the current user.'),
      ),
  );
  
  $data['og']['selective'] = array(
    'title' => t('Group: Selective'),
    'help' => t('The group preference which determines how membership requests are managed (moderated, invite only, etc.).'),
    'field' => array(
      'handler' => 'views_handler_field_og_selective',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_og_selective',
    ),
  );
  $data['og']['private'] = array(
    'title' => t('Group: Private'),
    'help' => t('Is the group home page private or not.'),
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
    'title' => t('Group: List in Directory'),
    'help' => t('Admin specifies whether or not a group appears in the public listings.'),
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
  $data['og']['subscribe'] = array(
    'title' => t('Group: Join Link'),
    'help' => t('Displays a links for joining a group when a user is not already a member.'),
    'field' => array(
      'handler' => 'views_handler_field_og_subscribe',
    ),
  );
  $data['og']['notification'] = array(
    'title' => t('Group: Notification'),
    'help' => t('Does group sending email notifications to its members by default'),
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
  if (module_exists('locale')) {
    $data['og']['language'] = array(
      'title' => t('Group: Language'),
      'help' => t('Displays the language selected for a given group.'),
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
  
  // pseudofield
  // Node type
  $data['og']['type_groups'] = array(
    'title' => t('Group Types'), // The item it appears as on the UI,
    'help' => t('The type of a group (for example, "blog entry", "forum post", "story", etc).'),
    'real field' => 'type',
    'filter' => array(
      'handler' => 'views_handler_filter_og_type',
    ),
  );
  
  $data['og']['type_groups_all'] = array(
    'title' => t('Group Types (all)'), // The item it appears as on the UI,
    'help' => t('The type of a group (for example, "blog entry", "forum post", "story", etc).'),
    'real field' => 'type',
    'filter' => array(
      'handler' => 'views_handler_filter_og_type_all',
      'help' => t('Restrict to all content types that have been marked as groups.'),
    ),
  );
  
  return $data;
}

/**
 * We do not try to run db_rewrite_sql() on this subquery so the count is potenially more than the user can see.
 */
// oga.group_nid=n.nid is not right.
// class views_handler_field_og_post_count_new extends views_handler_field_numeric {
//   function query() {
//     global $user;
//     $sql = "SELECT COUNT(*) FROM {node} n INNER JOIN {og_ancestry} oga ON n.nid = oga.nid LEFT JOIN {history} h ON n.nid=h.nid AND h.uid = $user->uid WHERE n.status = 1 AND oga.group_nid = n.nid AND h.timestamp IS NULL AND n.created > ". NODE_NEW_LIMIT;
//     dsm($sql);
//     $this->query->add_field('', "($sql)", 'post_count_new');
//     $this->field_alias = 'post_count_new';
//   }
// }

/**
 * We do not try to run db_rewrite_sql() on this subquery so the count is potenially more than the user can see.
 */
class views_handler_field_og_post_count extends views_handler_field_numeric {
  function query() {
    $sql = "SELECT COUNT(n.nid) FROM {node} n INNER JOIN {og_ancestry} oga ON n.nid = oga.nid WHERE n.status = 1 AND oga.group_nid = %d";
    $sql = str_replace('%d', 'og.nid', $sql);
    $this->query->add_field('', "($sql)", 'post_count');
    $this->field_alias = 'post_count';
  }
}

class views_handler_field_og_member_count extends views_handler_field_numeric {
  function query() {
    $sql = og_list_users_sql(1, 0, NULL, TRUE);
    $sql = str_replace('%d', 'og.nid', $sql);
    $this->query->add_field('', "($sql)", 'member_count');
    $this->field_alias = 'member_count';
  }
}

class views_handler_filter_og_type_all extends views_handler_filter {
  function query() {
    $group_types = og_get_types('group');
    $placeholders = db_placeholders($group_types, 'varchar');
    // todoh: use aliases properly
    $this->query->add_where($this->options['group'], "node.type IN ($placeholders)", $group_types);
  }
}

// TODOL: query() does not work.
class views_handler_filter_og_type extends views_handler_filter_node_type {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Group node type');
      $group_types = og_get_types('group');
      $types = node_get_types();
      foreach ($group_types as $group_type) {
        $options[$group_type] = $types[$group_type]->name;
      }
      $this->value_options = $options;
    }
  }  
}


/**
 * Field handler to show Selective state.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_og_selective extends views_handler_field {
  function render($values) {
    $map = og_selective_map();
    return $map[$values->{$this->field_alias}];
  }
}


 /*
 * Field handler to filter by Selective state
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_og_selective extends views_handler_filter_in_operator {
  function get_value_options() {
    if (isset($this->value_options)) {
      return;
    }

    $this->value_options = array();
    foreach (og_selective_map() as $key => $name) {
      $this->value_options[$key] = $name;
    }
  }
}


/**
 * Field handler to allow show 'join' link or empty if already member.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_og_subscribe extends views_handler_field {
  function construct() {
    parent::construct();
    $this->additional_fields['selective'] = 'selective';
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    global $user;
    if (!in_array($values->nid, array_keys($user->og_groups))) {
      switch ((int)$values->{$this->aliases['selective']}) {
        case OG_CLOSED:
          return '<em>'. t('Closed'). '</em>';
        case OG_INVITE_ONLY:
          return '<em>'. t('Invite only'). '</em>';
        default:
          return og_subscribe_link(node_load((int)$values->nid));
      }
    }
  }
}

// ------------ Table 'og_ancestry'

function og_views_data_og_ancestry() {
  $data['og_ancestry']['table']['group']  = t('Organic groups');
  $data['og_ancestry']['table']['base'] = array(
    'field' => 'nid',
    'title' => t('Group posts'),
    'help' => t("Posts which are affiiated with a group."),
  );

  $data['og_ancestry']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );
  
  // todoh Add setting of context
  $filterhelp = t('Filter for posts in specified organic groups. <em>Value</em> should be a one or more node ids (i.e. integers), separated by commas or pluses. Like taxonomy, pluses indicate that the post must appear in one of the specified groups, whereas commas indicate that the post must appear in every specified group. The page context (if View is a page) will be set to the first listed group. That means that blocks and breadcrumbs (and theme and locale, if applicable) will be based upon the first specified node id.');
  $data['og_ancestry']['group_nid'] = array(
    'title' => t('Groups'),
    'help' => t('The groups for a post.'),
    'field' => array(
      'handler' => 'views_handler_field_og_group_nids',
    ),
    'relationship' => array(
      'title' => t('Group node'),
      'help' => t('Bring in information about the group node.'),
      'base' => 'node',
      'field' => 'group_nid',
      'handler' => 'views_handler_relationship',
      'label' => t('Group'),
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'numeric' => TRUE,
      'help' => $filterhelp,
    ),
    'argument' => array(
      'name' => t('Post: in specified group (by number)'),
      'name field' => 'title', // the field to display in the summary.
      'validate type' => 'nid', // TODOH: not sure. copied from node.nid
      'handler' => 'views_handler_argument_og_group_nid',
      'numeric' => TRUE,
      'help' => $filterhelp,
    ),
  );
  
  $data['og_ancestry']['nid'] = array(
    'title' => t('Post: Nid'),
    'help' => t('The node ID of the node.'),
    'field' => array(
      'handler' => 'views_handler_field_node',
    ),    
  );
  
  // TODOH: set context
  $data['og_ancestry']['picg'] = array(
    'title' => t('OG: Posts in current group'),
    'help' => t('Posts in current group. Useful for blocks where determining context is hard. If page is not in any group context, no nodes are listed and thus a block would not appear.'),
    'filter' => array(
      'handler' => 'views_handler_filter_og_picg',
    ),
  );
  
  // TOODL: yes_empty field
  $data['og_ancestry']['is_public'] = array(
    'title' => t('Post: Public'),
    'help' => t('Displays <em>yes</em> if post is public according to OG.'),
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

class views_handler_argument_og_group_nid extends views_handler_argument_many_to_one {
  /**
   * Override the behavior of title(). Get the title of the node.
   */
  function title() {
    // TODOL: how to handle multiple arguments.
    $title = db_result(db_query(db_rewrite_sql("SELECT n.title FROM {node} n WHERE n.nid = %d"), $this->argument));
    if (empty($title)) {
      return t('No title');
    }

    return check_plain($title);
  }
}

class views_handler_field_og_group_nids extends views_handler_field_prerender_list {
  function init(&$view, $options) {
    parent::init($view, $options);
    $this->additional_fields['nid'] = array('table' => 'og_ancestry', 'field' => 'nid');
  }
  
  /**
   * Add this term to the query
   */
  function query() {
    $this->add_additional_fields();
  }
  
  // we have to query to get all the groups into a single element
  function pre_render($values) {
    // field_alias is used by our render method.
    $this->field_alias = $this->aliases['nid'];
    foreach ($values as $value) {
      $result = og_get_node_groups_result($value->nid);
      while ($row = db_fetch_object($result)) {
        $this->items[$value->nid][$row->group_nid] = l($row->title, "node/$row->group_nid");
      }
    }
  }
}

/**
 * Lovely filter handler which restricts posts to the current group. Useful for group related blocks.
 **/
class views_handler_filter_og_picg extends views_handler_filter {
  function query() {
    $table = $this->ensure_my_table();
    $this->query->add_where($this->options['group'], "$table.group_nid  = ***CURRENT_GID***");
  }
  
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
      // Not desireable anymore. We need to list pending requests.
      // 'extra' => array(
      //        array('field' => 'is_active', 'value' => 1, 'numeric' => TRUE),
      //      ),
    ),
  );
  
  $data['og_uid']['nid'] = array(
    'title' => t('Group'),
    'help' => t('Group that a member belongs to.'),
    'filter' => array(
      'handler' => 'views_handler_filter_user_current',
      'help' => t('Posts are filtered to where current user is a member.'),
    ),
    'relationship' => array(
      'title' => t('Group node'),
      'help' => t('Bring in information about the group node.'),
      'base' => 'node',
      'field' => 'nid',
      'handler' => 'views_handler_relationship',
      'label' => t('Group'),
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_many_to_one',
      'help' => t('Posts are filtered to where current user is a member.'),
    ),
  ); 
  
  $data['og_uid']['uid'] = array(
    'title' => t('Group member'),
    // 'help' => t('foo')
    'filter' => array(
      'handler' => 'views_handler_filter_user_current',
      'help' => t('OG: Group in User\'s Groups'),
    ),
  );
  
    
  $data['og_uid']['mail_type'] = array(
    'title' => t('OG: Notification email'),
    'help' => t('Does member receive email notifications for a group.'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['og_uid']['managelinkadmin'] = array(
    'title' => t('OG: Admin Manage link'),
    'help' => t('A link to the <em>Admin create or remove</em> page.'),
    'field' => array(
      'handler' => 'views_handler_field_og_managelinkadmin',
      'click sortable' => FALSE,
    ),
  );
  $data['og_uid']['managelinkmy'] = array(
    'title' => t('OG: Manage membership link'),
    'help' => t('A link to the <em>My membership</em> page.'),
    'field' => array(
      'handler' => 'views_handler_field_og_managelinkmy',
      'click sortable' => FALSE,
    ),
  );
  $data['og_uid']['managelink'] = array(
    'title' => t('OG: Approve/Deny/Remove membership link'),
    'help' => t('A link to approve/deny/remove a group member.'),
    'field' => array(
      'handler' => 'views_handler_field_og_managelink',
      'click sortable' => FALSE,
    ),
  );
  $data['og_uid']['is_admin'] = array(
    'title' => t('OG: Is member an admin in a group'),
    'help' => t('Add <em>admin</em> text if user is the group manager.'),
    'field' => array(
      'handler' => 'views_handler_field_og_is_admin',
      'click sortable' => TRUE,
    ),
    'sort' => array(
       'handler' => 'views_handler_sort',
    ),
  );
  $data['og_uid']['is_active'] = array(
    'title' => t('OG: Is membership approved'),
    'help' => t('Add <em>approval needed</em> user if user\'s membership request is pending.'),
    'field' => array(
      'handler' => 'views_handler_field_og_is_active',
      'click sortable' => TRUE,
    ),
    'sort' => array(
       'handler' => 'views_handler_sort',
    ),
  );
  $data['og_uid']['is_manager'] = array(
    'title' => t('OG: Is the group manager'),
    'help' => t('Add <em>manager</em> text if user is the group manager.'),
    'field' => array(
      'handler' => 'views_handler_field_og_is_manager',
      'click sortable' => FALSE,
    ),
  );
    
  return $data;
}

/**
 * Field handler to show if user is admin or not.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_og_is_active extends views_handler_field {
  
  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }
  
  function render($values) {
    $is_active = $values->{$this->field_alias};
    if (!$is_active) {
      return '<em>('. t('approval needed'). ')</em>'; 
    }
  }

}

/**
 * Field handler to show if user is admin or not.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_og_is_admin extends views_handler_field {
  function render($values) {
     $is_admin = $values->{$this->field_alias};
    // don't use og_is__group_admin here since we don't want mark on 'admin nodes' people.
    if ($is_admin) {
      return '<em>'. t('admin'). '</em>'; 
    }
  }
}

/**
 * Field handler to show if user is manager or not of the displayed group node.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_og_is_manager extends views_handler_field {
  function construct() {
    parent::construct();
    $this->additional_fields['uid'] = array('table' => 'og_uid', 'field' => 'uid');
    $this->additional_fields['node_uid'] = array('table' => 'node', 'field' => 'uid');
  }
  
  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }
  
  function render($values) {
    $uid = $values->{$this->aliases['uid']};
    $node_uid = $values->{$this->aliases['node_uid']};
    if ($uid == $node_uid) {
      return '<em>'. t('manager'). '</em>'; 
    }
  }
}

/**
 * Field handler to add/remove an admin.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_og_managelinkadmin extends views_handler_field {
  function construct() {
    parent::construct();
    $this->additional_fields['uid'] = array('table' => 'og_uid', 'field' => 'uid');
    $this->additional_fields['is_admin'] = array('table' => 'og_uid', 'field' => 'is_admin');
    $this->additional_fields['nid'] = array('table' => 'og_uid', 'field' => 'nid');
  }
  
  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }
  
  function render($values) {
    $nid = $values->{$this->aliases['nid']};
    $uid = $values->{$this->aliases['uid']};
    $is_admin = $values->{$this->aliases['is_admin']};
    $node = node_load($nid);
    // Only show links to group admins. And don't show next to group manager.
    if (og_is_group_admin($node) && $uid != $node->uid) {
      if ($is_admin) {
        return l(t('admin: remove'), "og/delete_admin/$nid/$uid", array('query' => drupal_get_destination()));
      }
      else {
        return l(t('admin: create'), "og/create_admin/$nid/$uid", array('query' => drupal_get_destination()));
      } 
    }
  }
}

/**
 * Field handler to approve/deny a subscription request, or remove a member.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_og_managelink extends views_handler_field {
  function construct() {
    parent::construct();
    $this->additional_fields['is_active'] = array('table' => 'og_uid', 'field' => 'is_active');
    $this->additional_fields['uid'] = array('table' => 'og_uid', 'field' => 'uid');
    $this->additional_fields['nid'] = array('table' => 'og_uid', 'field' => 'nid');
  }
  
  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }
  
  function render($values) {
    $nid = $values->{$this->aliases['nid']};
    $uid = $values->{$this->aliases['uid']};
    $is_active = $values->{$this->aliases['is_active']};
    $node = node_load($nid);
    // Only show links to group admins. And don't show next to group manager.
    if (og_is_group_admin($node) && $uid != $node->uid) {
      if ($is_active) {
        return l(t('remove'), "og/unsubscribe/$nid/$uid", array('query' => drupal_get_destination()));
      }
      else {
        $approve = l(t('approve'), "og/approve/$nid/$uid", array('query' => drupal_get_destination()));
        $deny = l(t('deny'), "og/deny/$nid/$uid", array('query' => drupal_get_destination()));
        return t('Request: !a or !d', array('!a' => $approve, '!d' => $deny));
      }
    }
  }
}

class views_handler_field_og_managelinkmy extends views_handler_field {
  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    global $user;
    return l(t('My membership'), "og/manage/$values->nid", array('query' => drupal_get_destination()));
  }
}
  
  
  
