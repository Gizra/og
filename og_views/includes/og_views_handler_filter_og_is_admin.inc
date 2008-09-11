<?php
class og_views_handler_filter_og_is_admin extends views_handler_filter_in_operator {
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