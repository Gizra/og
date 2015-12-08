<?php

/**
 * @file
 * Contains \Drupal\og_ui\Controller\SubscriptionController.
 */

namespace Drupal\og_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\og\Og;
use Drupal\og\OgAccess;

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
    $entity_access = $this->entityTypeManager()->getAccessControlHandler($entity_type_id);
    $group = $entity_storage->load($entity_id);

    $account = User::load($this->currentUser()->id());
    $field_name = $request->query->get('field_name');

    if (empty($field_name)) {
      $field_name = og_get_best_group_audience_field('user', $account, $entity_type, $bundle);
      if (empty($field_name)) {
        throw new NotFoundHttpException();
      }
    }

    // @todo Requires FieldableEntityInterface/ContentEntityInterface
    $field = $group->getFieldDefinition($field_name);

    if (empty($instance) || !$entity_access->fieldAccess('view', $field, $account)) {
      // Field name given is incorrect, or user doesn't have access to the field.
      throw new NotFoundHttpException();
    }

    if ($account->isAnonymous()) {
      // Anonymous user can't request membership.
      $destination = $this->getDestinationArray();

      $user_login_url = Url::fromRoute('user.login', [], $destination);

      // @todo I think this is correct? Other options are visitors or require
      // approval both of which apply to the else instead of here.
      if ($this->config('user.settings')->get('register') === USER_REGISTER_ADMINISTRATORS_ONLY) {
        drupal_set_message($this->t('In order to join any group, you must <a href=":login">login</a>. After you have successfully done so, you will need to request membership again.', [':login' => $user_login_url]));
      }
      else {
        $user_register_url = Url::fromRoute('user.register', [], $destination);
        drupal_set_message($this->t('In order to join any group, you must <a href=":login">login</a> or <a href=":register">register</a> a new account. After you have successfully done so, you will need to request membership again.', [':register' => $user_register_url, ':login' => $user_login_url]));
      }

      return new RedirectResponse(Url::fromRoute('user.page')->setAbsolute(TRUE)->toString());
    }

    $redirect = FALSE;
    $message = '';
    $params = [
      '@user' => $account->getDisplayName(),
    ];

    // Show the group name only if user has access to it.
    $params['@group'] = $group->access('view', $account) ?  $group->label() : $this->t('Private group');

    if (Og::isMember($group, $account, [OG_STATE_BLOCKED])) {
      // User is blocked, access denied.
      throw new AccessDeniedHttpException();
    }

    if (Og::isMember($group, $account, [OG_STATE_PENDING])) {
      // User is pending, return them back.
      // @todo Amitai, what is the purpose of this $user->uid == $user->uid
      // check? This will always be true?!
      $message = $account->id() == $account->id() ? $this->t('You already have a pending membership for the group @group.', $params) : $this->t('@user already has a pending membership for the  the group @group.', $params);
      $redirect = TRUE;
    }

    if (Og::isMember($group, $account, [OG_STATE_ACTIVE])) {
      // User is already a member, return them back.
      $message = $account->id() == $account->id() ? $this->t('You are already a member of the group @group.', $params) : $this->t('@user is already a member of the group @group.', $params);
      $redirect = TRUE;
    }

    $cardinality = $field->getStorageDefinition()->getCardinality();

    if (!$message && ($cardinality != FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)) {
      // Check if user is already registered as active or pending in the maximum
      // allowed values.
      if ($cardinality === 1) {
        $count = $account->get($field_name)->value ? 1 : 0;
      }
      else {
        $count = $account->get($field_name)->count();
      }

      if ($count >= $cardinality) {
        $message = $this->t('You cannot register to this group, as you have reached your maximum allowed subscriptions.');
        $redirect = TRUE;
      }
    }

    if ($redirect) {
      drupal_set_message($message, 'warning');
      return new RedirectResponse($group->toUrl()->setAbsolute(TRUE)->toString());
    }

    if (OgAccess::userAccess($group, 'subscribe', $account) || OgAccess::userAccess($group, 'subscribe without approval', $account)) {
      // Show the user a subscription confirmation.
      return $this->formBuilder()->buildForm('og_ui_confirm_subscribe', $group, $account, $field_name);
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

    $account = $this->currentUser();

    // Check the user isn't the manager of the group.
    if (($group instanceof EntityOwnerInterface) && ($group->getOwnerId() !== $account->id())) {
      if (Og::isMember($group, $account, [OG_STATE_ACTIVE, OG_STATE_PENDING])) {
        // Show the user a subscription confirmation.
        return $this->formBuilder()->buildForm('og_ui_confirm_unsubscribe', $group);
      }

      throw new AccessDeniedHttpException();
    }

    drupal_set_message(t('As the manager of %group, you can not leave the group.', array('%group' => $group->label())));

    return new RedirectResponse($group->toUrl()->setAbsolute(TRUE)->toString());
  }

}
