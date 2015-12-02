<?php

/**
 * @file
 * Contains \Drupal\og_ui\Controller\SubscriptionController.
 */

namespace Drupal\og_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for OG subscription routes.
 */
class SubscriptionController extends ControllerBase {

  /**
   * @param string $entity_type_id
   * @param string|int $entity_id
   *
   * @return mixed
   */
  public function subscribe(Request $request, $entity_type_id, $entity_id) {
    // @todo We don't need to re-validate the entity type and entity group here,
    // as it's already been done in the access check?
    $entity_storage = $this->entityTypeManager()->getStorage($entity_type_id);
    $group = $entity_storage->load($entity_id);

    $account = $this->currentUser()->getAccount();
    $field_name = $request->query->get('field_name');

    if (empty($field_name)) {
      $field_name = og_get_best_group_audience_field('user', $account, $entity_type, $bundle);
      if (empty($field_name)) {
        throw new NotFoundHttpException();
      }
    }

    $field = field_info_field($field_name);
    $instance = field_info_instance('user', $field_name, 'user');
    if (empty($instance) || !field_access('view', $field, 'user', $account)) {
      // Field name given is incorrect, or user doesn't have access to the field.
      throw new NotFoundHttpException();
      return;
    }
    if ($account->isAnonymous()) {
      // Anonymous user can't request membership.
      $dest = drupal_get_destination();
      if (variable_get('user_register', 1)) {
        drupal_set_message($this->t('In order to join any group, you must <a href="!login">login</a>. After you have successfully done so, you will need to request membership again.', array('!login' => url("user/login", array('query' => $dest)))));
      }
      else {
        drupal_set_message($this->t('In order to join any group, you must <a href="!login">login</a> or <a href="!register">register</a> a new account. After you have successfully done so, you will need to request membership again.', array('!register' => url("user/register", array('query' => $dest)), '!login' => url("user/login", array('query' => $dest)))));
      }
      drupal_goto('user');
    }

    $redirect = FALSE;
    $message = '';
    $params = array();
    $params['@user'] = format_username($user);

    // Show the group name only if user has access to it.
    $params['@group'] = entity_access('view', $entity_type, $entity) ?  entity_label($entity_type, $entity) : $this->t('Private group');

    if (og_is_member($entity_type, $id, 'user', $user, array(OG_STATE_BLOCKED))) {
      // User is blocked, access denied.
      throw new AccessDeniedHttpException();
    }

    if (og_is_member($entity_type, $id, 'user', $user, array(OG_STATE_PENDING))) {
      // User is pending, return them back.
      $message = $user->uid == $user->uid ? $this->t('You already have a pending membership for the group @group.', $params) : $this->t('@user already has a pending membership for the  the group @group.', $params);
      $redirect = TRUE;
    }

    if (og_is_member($entity_type, $id, 'user', $user, array(OG_STATE_ACTIVE))) {
      // User is already a member, return them back.
      $message = $user->uid == $user->uid ? $this->t('You are already a member of the group @group.', $params) : $this->t('@user is already a member of the group @group.', $params);
      $redirect = TRUE;
    }

    if (!$message && $field['cardinality'] != FIELD_CARDINALITY_UNLIMITED) {
      // Check if user is already registered as active or pending in the maximum
      // allowed values.
      $wrapper = entity_metadata_wrapper('user', $account->uid);
      if ($field['cardinality'] == 1) {
        $count = $wrapper->{$field_name}->value() ? 1 : 0;
      }
      else {
        $count = $wrapper->{$field_name}->count();
      }

      if ($count >= $field['cardinality']) {
        $message = $this->t('You cannot register to this group, as you have reached your maximum allowed subscriptions.');
        $redirect = TRUE;
      }
    }

    if ($redirect) {
      drupal_set_message($message, 'warning');
      $url = entity_uri($entity_type, $entity);
      drupal_goto($url['path'], $url['options']);
    }

    if (og_user_access($entity_type, $id, 'subscribe', $user) || og_user_access($entity_type, $id, 'subscribe without approval', $user)) {
      // Show the user a subscription confirmation.
      return drupal_get_form('og_ui_confirm_subscribe', $entity_type, $id, $user, $field_name);
    }

    throw new AccessDeniedHttpException();
  }

  /**
   * @param string $entity_type_id
   * @param string|int $entity_id
   */
  public function unsubscribe($entity_type_id, $entity_id) {
    // @todo We don't need to re-validate the entity type and entity group here,
    // as it's already been done in the access check?
    $entity_storage = $this->entityTypeManager()->getStorage($entity_type_id);
    $group = $entity_storage->load($entity_id);

    // Check the user isn't the manager of the group.
    if ($group->uid != $user->uid) {
      if (og_is_member($group_type, $gid, 'user', $account, array(OG_STATE_ACTIVE, OG_STATE_PENDING))) {
        // Show the user a subscription confirmation.
        return drupal_get_form('og_ui_confirm_unsubscribe', $group_type, $group);
      }

      throw new AccessDeniedHttpException();
    }

    $label = entity_label($group_type, $group);
    drupal_set_message(t('As the manager of %group, you can not leave the group.', array('%group' => $label)));
    $url = entity_uri($group_type, $group);
    drupal_goto($url['path'], $url['options']);
  }

}
