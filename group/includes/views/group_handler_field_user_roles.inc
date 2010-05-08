<?php
// $Id:$
/**
 * Field handler to provide a list of group roles.
 */
class group_handler_field_user_roles extends views_handler_field_prerender_list {
  function construct() {
    parent::construct();
    $this->additional_fields['uid'] = array('table' => 'users', 'field' => 'uid');
  }

  function pre_render($values) {
    $list = array();
    $this->items = array();
    foreach ($values as $value) {
      $list[] = array(
        'uid' => $value->{$this->aliases['uid']},
        'gid' => $value->{$this->field_alias},
      );
    }

    // Array of all the group IDs we will need to get a role name.
    $gids = array();
    if ($list) {
      foreach ($list as $value) {
        $rids = group_get_user_roles($value['gid'], $value['uid']);
        $gids[$value['gid']] = $value['gid'];
        foreach ($rids as $rid) {
          $this->items[$value['gid']][$value['uid']][$rid] = array('rid' => $rid);
        }
      }
    }
  }


  /**
   * We override this function as $this->items is an array with the:
   * - Group ID
   * -- User ID
   * --- Role ID
   */
  function advanced_render($values) {
    if ($this->allow_advanced_render() && method_exists($this, 'render_item')) {
      $raw_items = $this->get_items($values);
    }
    else {
      $this->last_render = $value = $this->render($values);
      $this->original_value = $value;
    }

    if ($this->allow_advanced_render()) {
      $tokens = NULL;
      if (method_exists($this, 'render_item')) {
        $items = array();
        // Override parent logic -- make sure we get an item per group, per
        // user, and not try to get the roles per group only.
        $uid = $values->{$this->aliases['uid']};
        if (!empty($raw_items[$uid])) {
          foreach ($raw_items[$uid] as $count => $item) {
            $this->last_render = $this->render_item($count, $item);
            $this->original_value = $this->last_render;

            $alter = $item + $this->options['alter'];
            $items[] = $this->render_text($alter);
          }
        }

        $value = $this->render_items($items);
      }
      else {
        $value = $this->render_text($this->options['alter']);
      }

      // This happens here so that render_as_link can get the unaltered value of
      // this field as a token rather than the altered value.
      $this->last_render = $value;
    }

    if (empty($this->last_render)) {
      if (($this->last_render !== 0 && $this->last_render !== '0') || !empty($this->options['empty_zero'])) {
        $this->last_render = $this->options['empty'];
      }
    }

    return $this->last_render;
  }


  function render_item($rid, $item) {
    return $item['rid'];
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
