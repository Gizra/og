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
      'handler' => 'views_handler_field_og_member_count',
      'click sortable' => TRUE,
      'help' => t('Number of members for a group. Excludes memberships which are pending approval.'),
    ),
  );
  $data['og']['post_count'] = array(
    'title' => t('Group: Post count'),
    'field' => array(
      'handler' => 'views_handler_field_og_post_count',
      'click sortable' => TRUE,
      'help' => t('Number of published posts in a group. Can be restricted by node type using the <em>Option</em> dropdown.'),
      'notafield' => TRUE,
     ),
  );
  $data['og']['post_count_new'] = array(
      'title' => t('Group: Post count *new*'),
      'field' => array(
        'handler' => 'views_handler_field_og_post_count_new',
        'click sortable' => TRUE,
        'help' => t('Number of new posts in a group for the current user.'),
      ),
  );
  
  $data['og']['selective'] = array(
    'title' => t('Group: Selective'),
    'help' => t('The group preference which determines how membership requests are managed (moderated, invite only, etc.).'),
    'real field' => 'og_selective',
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
    'help' => t('Displays a links for joining a group when a user is not already a member.'),
    'field' => array(
      'handler' => 'views_handler_field_og_subscribe',
    ),
  );
  $data['og']['notification'] = array(
    'title' => t('Group: Notification'),
    'help' => t('Does group sending email notifications to its members by default'),
    'real field' => 'og_notification',
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
  
  // pseudofield
  // Node type
  $data['og']['type_groups'] = array(
    'title' => t('Group types'), // The item it appears as on the UI,
    'help' => t('The type of a group (for example, "blog entry", "forum post", "story", etc).'),
    'real field' => 'type',
    'filter' => array(
      'handler' => 'views_handler_filter_og_type',
    ),
  );
  
  $data['og']['type_groups_all'] = array(
    'title' => t('Group types (all)'), // The item it appears as on the UI,
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
 * We do not try to run db_rewrite_sql() on this subquery so the count is potentially more than the user can see.
 */
class views_handler_field_og_post_count extends views_handler_field_numeric {
  function query() {
    $table = $this->query->ensure_table('og');
    $sql = "SELECT COUNT(n.nid) FROM {node} n INNER JOIN {og_ancestry} oga ON n.nid = oga.nid WHERE n.status = 1 AND oga.group_nid = %d";
    $sql = str_replace('%d', 'og.nid', $sql);
    $this->query->add_field('', "($sql)", 'post_count');
    $this->field_alias = 'post_count';
  }
}

/**
 * We do not try to run db_rewrite_sql() on this subquery so the count is potentially more than the user can see.
 */
class views_handler_field_og_post_count_new extends views_handler_field_numeric {
  function query() {
    global $user;
    $table = $this->query->ensure_table('og');
    $sql = "SELECT COUNT(*) FROM {node} n INNER JOIN {og_ancestry} oga ON n.nid = oga.nid LEFT JOIN {history} h ON n.nid=h.nid AND h.uid = %uid WHERE n.status = 1 AND oga.group_nid = $table.nid AND h.timestamp IS NULL AND n.created > %created";
    $sql = strtr($sql, array('%uid' => $user->uid, '%created' => NODE_NEW_LIMIT));
    $this->query->add_field('', "($sql)", 'post_count_new');
    $this->field_alias = 'post_count_new';
  }
  
  function render($values) {
    if ($cnt = $values->{$this->field_alias}) {
      return $cnt. theme('mark', MARK_NEW);
    }
  }
}

class views_handler_field_og_member_count extends views_handler_field_numeric {
  function query() {
    $table = $this->query->ensure_table('og');
    $sql = og_list_users_sql(1, 0, NULL, TRUE);
    $sql = str_replace('%d', 'og.nid', $sql);
    $this->query->add_field('', "($sql)", 'member_count');
    $this->field_alias = 'member_count';
  }
  
  function render($values) {
    $nid = $values->nid;
    $txt = $values->member_count;
    if (og_is_group_member($nid)) {
      return og_is_picture() ? l($txt, "og/users/$nid/faces") : l($txt, "og/users/$nid");
    }
    else {
      return parent::render($values);
    }
  }
}

class views_handler_filter_og_type_all extends views_handler_filter {
  function query() {
    if ($group_types = og_get_types('group')) {
      $placeholders = db_placeholders($group_types, 'varchar');
      $table = $this->query->ensure_table('node');
      $this->query->add_where($this->options['group'], "$table.type IN ($placeholders)", $group_types);
    }
    else {
      $this->query->add_where($this->options['group'], "FALSE");
      drupal_set_message(t('You have no node types which are acting as groups. See the notes section of the !readme_file and the content types fieldset at top of <a href="!settings">OG settings</a>.', array('!readme_file' => og_readme(), '!settings' => url('admin/og/og'))), 'error');
    }
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
    $this->additional_fields['og_selective'] = 'og_selective';
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    global $user;
    if (!in_array($values->nid, array_keys($user->og_groups))) {
      switch ((int)$values->{$this->aliases['og_selective']}) {
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
    'help' => t('Posts which are affiiated with a group.'),
  );

  $data['og_ancestry']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );
  
  $filterhelp = t('<strong>Posts</strong> are filtered for specified organic groups. The page context (if Display is a page) will be set to the first listed group. That means that blocks and breadcrumbs (and theme and locale, if applicable) will be based upon the first specified node id.');
  $data['og_ancestry']['group_nid'] = array(
    'title' => t('Groups'),
    'help' => t('The groups for a post.'),
    'field' => array(
      'handler' => 'views_handler_field_og_group_nids',
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
      'handler' => 'views_handler_filter_og_group_nid',
      'numeric' => TRUE,
      'help' => $filterhelp,
    ),
    'argument' => array(
      'name' => t('Post: in specified group (by number)'),
      'name field' => 'title', // the field to display in the summary.
      'validate type' => 'nid',
      'handler' => 'views_handler_argument_og_group_nid',
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

class views_handler_filter_og_group_nid extends views_handler_filter_numeric {
  function query() {
    $node = node_load((int)$this->value['value']);
    og_set_group_context($node);
    parent::query();
  }
}

class views_handler_argument_og_group_nid extends views_handler_argument_numeric {
  // Set context based on first node passed.
  function query() {
    $args = explode(',', $this->argument);
    $node = node_load((int)$args[0]);
    og_set_group_context($node);
    parent::query();
  }
  
  /**
   * Override the behavior of title(). Get the title of the node.
   */
  function title_query() {
    $titles = array();
    $placeholders = implode(', ', array_fill(0, sizeof($this->value), '%d'));

    $result = db_query("SELECT n.title FROM {node} n WHERE n.nid IN ($placeholders)", $this->value);
    while ($term = db_fetch_object($result)) {
      $titles[] = check_plain($term->title);
    }
    return $titles;
  }
}

class views_handler_field_og_group_nids extends views_handler_field_prerender_list {
  function init(&$view, $options) {
    parent::init($view, $options);
    $this->additional_fields['og_ancestry_nid'] = array('table' => 'og_ancestry', 'field' => 'nid');
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
    $this->field_alias = $this->aliases['og_ancestry_nid'];
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
      'handler' => 'views_handler_argument_og_uid_nid',
      'help' => t('<strong>Members</strong> are filtered for a specific group.'),
    ),
  ); 
  
  $data['og_uid']['uid'] = array(
    'title' => t('Group member'),
    // 'help' => t('foo')
    'filter' => array(
      'handler' => 'views_handler_filter_user_current',
      'help' => t("OG: Group in user's groups"),
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
    'title' => t('OG: Admin manage link'),
    'help' => t('A link to the <em>Admin create or remove</em> page.'),
    'field' => array(
      'handler' => 'views_handler_field_og_managelinkadmin',
      'click sortable' => FALSE,
    ),
  );
  $data['og_uid']['managelinkmy'] = array(
    'title' => t('OG: Edit membership link'),
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
    'filter' => array(
      'handler' => 'views_handler_filter_og_is_admin',
    ),
    'sort' => array(
       'handler' => 'views_handler_sort',
    ),
  );
  $data['og_uid']['is_active'] = array(
    'title' => t('OG: Is membership approved'),
    'help' => t("Add <em>approval needed</em> user if user's membership request is pending."),
    'field' => array(
      'handler' => 'views_handler_field_og_is_active',
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
      'handler' => 'views_handler_field_og_is_manager',
      'click sortable' => FALSE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
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

/**
 * Allow Group ID(s) as argument
 *
 * @ingroup views_argument_handlers
 */
class views_handler_argument_og_uid_nid extends views_handler_argument_numeric {
  // TODOL: set context?
  
  /**
   * Give an argument the opportunity to modify the breadcrumb, if it wants.
   * This only gets called on displays where a breadcrumb is actually used.
   *
   * The breadcrumb will be in the form of an array, with the keys being
   * the path and the value being the already sanitized title of the path.
   */
  function set_breadcrumb(&$breadcrumb) { 
    $nid = $this->value[0];;
    // TODOL: not working.
    // $breadcrumb = og_views_set_breadcrumb($nid);
  }
  
}

class views_handler_filter_og_is_admin extends views_handler_filter_in_operator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Member types');
      $options = array(t('Members'), t('Group administrators'));
      $this->value_options = $options;
    }
  }
  
  // 0 won't work as a key for checkboxes.
  function value_form(&$form, &$form_state) { 
    parent::value_form($form, $form_state); 
    $form['value']['#type'] = 'select'; 
    $form['value']['#multiple'] = TRUE; 
  }
  
  // Remove problematic array_filter().
  function value_submit($form, &$form_state) {
    $form_state['values']['options']['value'] = $form_state['values']['options']['value'];
  }
}

/**
 * Field handler to show if user is admin or not.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_og_is_active extends views_handler_field {
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
    // don't use og_is_group_admin() here since we don't want mark on 'admin nodes' people.
    if ($is_admin) {
      return t('admin'); 
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
    // TODOL: Sometimes node table is not recognized (i.e. when listing users). 
    $this->additional_fields['node_uid'] = array('table' => 'node', 'field' => 'uid');
  }
  
  function query() {
    $this->ensure_my_table();
    $this->query->ensure_table('node');
    $this->add_additional_fields();
  }
  
  function render($values) {
    $uid = $values->{$this->aliases['uid']};
    $node_uid = $values->{$this->aliases['node_uid']};
    if ($uid == $node_uid) {
      return '<em>'. t('Manager'). '</em>'; 
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
        return l(t('Admin: Remove'), "og/delete_admin/$nid/$uid", array('query' => drupal_get_destination()));
      }
      else {
        return l(t('Admin: Create'), "og/create_admin/$nid/$uid", array('query' => drupal_get_destination()));
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
        return l(t('Remove membership'), "og/unsubscribe/$nid/$uid", array('query' => drupal_get_destination()));
      }
      else {
        $token = og_get_token($nid);
        return t('Request: <a href="@approve">approve</a> or <a href="@deny">deny</a>.', array('@approve' => url("og/approve/$nid/$uid/$token", array('query' => drupal_get_destination())), '@deny' => url("og/deny/$nid/$uid/$token", array('query' => drupal_get_destination()))));
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
    return l(t('Edit membership'), "og/manage/$values->nid", array('query' => drupal_get_destination()));
  }
}
  
/**
 * An implementation of hook_views_plugins().
 */
function og_views_plugins() {
  return array(
    'argument validator' => array(
      'og_group_types' => array(
        'title' => t('Group nodes'),
        'handler' => 'views_plugin_argument_validate_og_group_types',
      ),
    ),
  );
}

/**
 * Validate whether an argument is a group node. Borrows heavily form the Node argument validator.
 */
class views_plugin_argument_validate_og_group_types extends views_plugin_argument_validate {
  // What does this do?
  var $option_name = 'validate_argument_og_group_types';

  function validate_form(&$form, &$form_state) {
    $form['validate_argument_nid_type'] = array(
      '#type' => 'select',
      '#title' => t('Argument type'),
      '#options' => array(
        'nid' => t('Node ID'),
        'nids' => t("Node ID's separated by , or +"),
      ),
      '#default_value' => isset($this->argument->options['validate_argument_nid_type']) ? $this->argument->options['validate_argument_nid_type'] : 'nid',
      '#process' => array('views_process_dependency'),
      '#dependency' => array('edit-options-validate-type' => array($this->id)),
    );
    
    $form['validate_argument_is_member'] = array(
      '#type' => 'checkbox',
      '#title' => t('Validate current user is a member of a specified group'),
      '#default_value' => !empty($this->argument->options['validate_argument_is_member']),
      '#process' => array('views_process_dependency'),
      '#dependency' => array('edit-options-validate-type' => array($this->id)),
    );
  }

  function validate_argument($argument) {
    $types = og_get_types('group');

    $type = isset($this->argument->options['validate_argument_nid_type']) ? $this->argument->options['validate_argument_nid_type'] : 'nid';
    switch ($type) {
      case 'nid':
        if (!is_numeric($argument)) {
          return FALSE;
        }
        $node = node_load($argument);
        if (!$node) {
          return FALSE;
        }

        if (!og_is_group_type($node->type)) {
          return FALSE;
        }
        
        if (!empty($this->argument->options['validate_argument_is_member'])) {
          if (!og_is_group_member($node->nid)) {
            return FALSE;
          }
        }

        // Save the title() handlers some work.
        $this->argument->validated_title = check_plain($node->title);

        // Admin has not setup any content types to behave as a group. Thats unsupported.
        if (empty($types)) {
          return TRUE;
        }
        
        return TRUE;
      case 'nids':
        $nids = new stdClass();
        $nids->value = array($argument);
        $nids = views_break_phrase($argument, $nids);
        if ($nids->value == -1) {
          return FALSE;
        }

        $placeholders = implode(', ', array_fill(0, sizeof($nids->value), '%d'));

        $has_membership = FALSE;
        $titles = array();
        $test_nids = drupal_map_assoc($nids->value);

        $result = db_query("SELECT nid, type, title FROM {node} WHERE nid IN ($placeholders)", $nids->value);
        while ($node = db_fetch_object($result)) {
          if (!og_is_group_type($node->type)) {
            return FALSE;
          }

          if (!empty($this->argument->options['validate_argument_is_member'])) {
            // If user is a member of any of the specified groups, then she has access.
            if (!$has_membership && og_is_group_member($node->nid)) {
              $has_membership = TRUE;
            }
          }
          else {
            $has_membership = TRUE;
          }

          $titles[] = check_plain($node->title);
          unset($test_nids[$node->nid]);
        }

        $this->argument->validated_title = implode($nids->operator == 'or' ? ' + ' : ', ', $titles);
        // If $test is not empty, we did not find a nid.
        return empty($test_nids) && $has_membership;
    }
  }
}

function og_views_set_breadcrumb($nid) {
  $node = node_load((int)$nid);
  $bc = array(
    '<front>' => t('Home'),
    "node/$nid" => check_plain($node->title),
  );
  return $bc;
}