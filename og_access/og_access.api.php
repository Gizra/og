<?php


/**
 * @file
 * Hooks provided by the Organic groups access module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allow modules to mark group privacy change.
 *
 * @param &$context
 *   Array including 3 keys:
 *   'entity' - the submitted group entity.
 *   'entity_type' - String. The type of the entity that was submitted.
 *   'change_detected' - Boolean. Marks whether the group privacy changed or not.
 *                       FALSE by default, and changeable be each hook
 *                       implementation.
 */
function hook_og_access_invoke_node_access_acquire_grants_alter(&$context) {
  $wrapper = entity_metadata_wrapper($context['entity_type'], $context['entity']);
  $original_wrapper = entity_metadata_wrapper($context['entity_type'], $context['entity']->original);

  $og_access = $wrapper->{OG_ACCESS_FIELD}->value();
  $orig_og_access = $original_wrapper->{OG_ACCESS_FIELD}->value();
  if ($og_access !== $orig_og_access) {
    $context['change_detected'] = TRUE;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
