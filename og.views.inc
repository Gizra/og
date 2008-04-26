<?php
// $Id$

function og_views_data() {
  $data = array();
  $tables = array('og');
  foreach ($tables as $table) {
    $function = "og_views_data_$table";
    $data += $function();
  }
  return $data;
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
  $data['og']['count'] = array(
    'title' => t('Group: Members Count'),
    'field' => array(
      'handler' => 'og_handler_field_count',
      'click sortable' => FALSE,
      'help' => t('Number of members for a group. Excludes memberships which are pending approval.'),
      'notafield' => TRUE,
    ),
  );
  $data['og']['post_count'] = array(
    'title' => t('Group: Post Count'),
    'field' => array(
      'handler' => 'og_handler_post_count',
      'click sortable' => FALSE,
      'help' => t('Number of published posts in a group. Can be restricted by node type using the <em>Option</em> dropdown.'),
      'notafield' => TRUE,
     ),
     // 'option' => array(
          //             '#type' => 'select',
          //             '#options' => array_merge(array('all' => t('All')), $group_post_type_options),
          //             '#default_value' => 'all',
          //             // View will not save with #multiple, for some reason. Help.
          //             // '#multiple' => TRUE,
          //           ),
  );
  $data['og']['post_count_new'] = array(
      'title' => t('Group: Post Count *New*'),
      'field' => array(
        'handler' => 'og_handler_post_count_new',
        'sortable' => FALSE,
        'notafield' => TRUE,
        'help' => t('Number of new posts in a group for the current user. The <em>new</em> indicator may be optionally appended using the <em>Option</em> dropdown.'),
        // 'option' => array(
        //    '#type' => 'select',
        //    '#options' => array(
        //      'normal' => 'Normal',
        //      'withmark' => 'With new mark'
        //     ),
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
    'field' => array(
      'handler' => 'views_handler_field_boolean',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
      'label' => t('Is the group home page private or not.'),
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
  );
  $data['og']['subscribe'] = array(
    'field' => array(
      'handler' => 'views_handler_field_og_subscribe',
      'title' => t('Group: Join Link'),
      'help' => t('Displays a links for joining a group when a user is not already a member.'),
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
  );
  if (module_exists('translation')) {
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
  
  return $data;
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

