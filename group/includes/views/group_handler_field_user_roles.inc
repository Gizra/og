<?php
// $Id:$
/**
 * Field handler to provide a list of group roles.
 */
class group_handler_field_user_roles extends views_handler_field_prerender_list {
  function construct() {
    parent::construct();
    $this->additional_fields['uid'] = array('table' => 'users', 'field' => 'uid');
    //$this->additional_fields['gid'] = array('table' => 'group_groups', 'field' => 'gid');
  }

  function query() {
    $this->add_additional_fields();
    $this->field_alias = $this->aliases['uid'];
  }

  function pre_render($values) {
    $list = array();
    $this->items = array();
    dpm($values);
    return;
    foreach ($values as $result) {
      $list[] = array(
        'uid' => $result->{$this->aliases['uid']},
        'gid' => $result->{$this->aliases['gid']},
      );
    }

    // Array of all the group IDs we will need to get a role name.
    $gids = array();
    if ($list) {
      foreach ($list as $value) {
        $rids = group_get_user_roles($value['gid'], $value['uid']);
        $gids[$value['gid']] = $value['gid'];
        foreach ($rids as $rid) {
          $this->items[$role->uid][$role->rid]['rid'] = $role->rid;
        }
      }
      // Get all the role ID names.

      foreach ($all_rids as $role) {
        $this->items[$role->uid][$role->rid]['role'] = check_plain($role->name);

      }
    }
  }

  function render_item($count, $item) {
    return $item['group role'];
  }

  function document_self_tokens(&$tokens) {
    $tokens['[' . $this->options['id'] . '-role' . ']'] = t('The name of the role.');
    $tokens['[' . $this->options['id'] . '-rid' . ']'] = t('The role ID of the role.');
  }

  function add_self_tokens(&$tokens, $item) {
    $tokens['[' . $this->options['id'] . '-role' . ']'] = $item['role'];
    $tokens['[' . $this->options['id'] . '-rid' . ']'] = $item['rid'];
  }
}
