<?php
class og_views_handler_field_og_managelinkmy extends views_handler_field {
  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['text'] = array('default' => 'Edit membership', 'translatable' => TRUE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
  }

  function render($values) {
    global $user;
    if (in_array($values->nid, array_keys($user->og_groups))) {
      $text = !empty($this->options['text']) ? $this->options['text'] : t('Edit membership');
      return l($text, "og/manage/$values->nid", array('query' => drupal_get_destination()));
    }
  }
}
