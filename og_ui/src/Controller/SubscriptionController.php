<?php

/**
 * @file
 * Contains \Drupal\og_ui\Controller\SubscriptionController.
 */

namespace Drupal\og_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\og\OgMembershipInterface;
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
   * @param NULL|string $field_name
   *
   * @return mixed
   */
  public function subscribe(Request $request, $entity_type_id, $entity_id, $field_name) {
    // @todo We don't need to re-validate the entity type and entity group here,
    // as it's already been done in the access check?
    $entity_storage = $this->entityTypeManager()->getStorage($entity_type_id);
    $entity_access = $this->entityTypeManager()->getAccessControlHandler($entity_type_id);
    $group = $entity_storage->load($entity_id);

    $account = User::load($this->currentUser()->id());

    if (empty($field_name)) {
      $field_name = og_get_best_group_audience_field('user', $account, $entity_type_id, $group->bundle());
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

      $user_login_url = Url::fromRoute('user.login', [], $destination)->toString();

      // @todo I think this is correct? Other options are visitors or require
      // approval both of which apply to the else instead of here.
      if ($this->config('user.settings')->get('register') === USER_REGISTER_ADMINISTRATORS_ONLY) {
        drupal_set_message($this->t('In order to join any group, you must <a href=":login">login</a>. After you have successfully done so, you will need to request membership again.', [':login' => $user_login_url]));
      }
      else {
        $user_register_url = Url::fromRoute('user.register', [], $destination)->toString();
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

    if (Og::isMemberBlocked($group, $account)) {
      // User is blocked, access denied.
      throw new AccessDeniedHttpException();
    }

    if (Og::isMemberPending($group, $account)) {
      // User is pending, return them back.
      $message = $this->t('@user already has a pending membership for the  the group @group.', $params);
      $redirect = TRUE;
    }

    if (Og::isMember($group, $account)) {
      // User is already a member, return them back.
      $message = $this->t('You are already a member of the group @group.', $params);
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
      // @todo Use buildForm() create our own form state object and attach
      // field_name etc..?
      return $this->formBuilder()->getForm('\Drupal\og_ui\Form\GroupSubscribeConfirmForm', $group, $account, $field_name);
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
      if (Og::isMember($group, $account, [OgMembershipInterface::STATE_ACTIVE, OgMembershipInterface::STATE_PENDING])) {
        // Show the user a subscription confirmation.
        return $this->formBuilder()->getForm('\Drupal\og_ui\Form\GroupUnsubscribeConfirmForm', $group);
      }

      throw new AccessDeniedHttpException();
    }

    drupal_set_message(t('As the manager of %group, you can not leave the group.', array('%group' => $group->label())));

    return new RedirectResponse($group->toUrl()->setAbsolute(TRUE)->toString());
  }

}
