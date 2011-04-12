<?php

/**
 * @file
 * Devel generate integration with Organic groups module.
 */

function og_devel_generate($object, $field, $instance, $bundle) {
  if (field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_CUSTOM) {
    return devel_generate_multiple('_og_devel_generate', $object, $field, $instance, $bundle);
  }
  else {
    return _og_devel_generate($object, $field, $instance, $bundle);
  }
}

function _og_devel_generate($object, $field, $instance, $bundle) {
  $object_field = array();
  if ($allowed_values = og_get_all_group()) {
    $keys = array_keys($allowed_values);
    $object_field['gid'] = $keys[mt_rand(0, count($allowed_values) - 1)];
  }
  return $object_field;
}
