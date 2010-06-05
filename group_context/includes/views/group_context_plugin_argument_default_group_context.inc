<?php
// $Id:$
/**
 * @file
 * Contains the group context argument default plugin.
 */

/**
 * The group context argument default handler.
 */
class group_context_plugin_argument_default_group_context extends views_plugin_argument_default {
  function option_definition() {
    $options = parent::option_definition();
    $options['argument'] = array('default' => array());

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $options = array('group' => t('Group')) + group_get_all_group_entity();
    $form['argument'] = array(
      '#type' => 'select',
      '#title' => t('Entity type'),
      '#description' => t('Select the entity type that that the group context should belong to.'),
      '#default_value' => $this->options['argument'],
      '#options' => $options,
    );
  }

  /**
   * Return the group context argument.
   */
  function get_argument() {
    $return = FALSE;
    if ($group = group_context()) {
      if ($this->options['argument'] == 'group' || $this->options['argument'] == $group->entity_type) {
        $return = $group->gid;
      }
    }
    return $return;
  }
}
