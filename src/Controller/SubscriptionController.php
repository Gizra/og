<?php

namespace Drupal\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
   * OG access service.
   *
   * @var \Drupal\og\OgAccess
   */
  protected $ogAccess;


  /**
   * Constructs a SubscriptionController object.
   *
   */
  public function __construct(OgAccess $og_access) {
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.access')
    );
  }

  /**
   * Subscribe a user to group.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $entity_type_id
   * @param string|int $entity_id
   * @param string|null $membership_type
   *
   * @return mixed
   */
  public function subscribe(Request $request, $entity_type_id, $entity_id, $membership_type = OgMembershipInterface::TYPE_DEFAULT) {
    $entity_storage = $this->entityTypeManager()->getStorage($entity_type_id);
    $group = $entity_storage->load($entity_id);

    $user = User::load($this->currentUser()->id());

    if ($user->isAnonymous()) {
      // Anonymous user can't request membership.
      $destination = $this->getDestinationArray();

      $user_login_url = Url::fromRoute('user.login', [], $destination)->toString();

      // @todo I think this is correct? Other options are visitors or require
      // approval both of which apply to the else instead of here.
      if ($this->config('user.settings')->get('register') === USER_REGISTER_ADMINISTRATORS_ONLY) {
        $params = [':login' => $user_login_url];
        drupal_set_message($this->t('In order to join any group, you must <a href=":login">login</a>. After you have successfully done so, you will need to request membership again.', $params));
      }
      else {
        $user_register_url = Url::fromRoute('user.register', [], $destination)->toString();
        $params = [':register' => $user_register_url, ':login' => $user_login_url];
        drupal_set_message($this->t('In order to join any group, you must <a href=":login">login</a> or <a href=":register">register</a> a new account. After you have successfully done so, you will need to request membership again.', $params));
      }

      return new RedirectResponse(Url::fromRoute('user.page')->setAbsolute(TRUE)->toString());
    }

    $redirect = FALSE;
    $message = '';
    $params = ['@user' => $user->getDisplayName()];

    // Show the group name only if user has access to it.
    $params['@group'] = $group->access('view', $user) ?  $group->label() : $this->t('Private group');

    if (Og::isMemberBlocked($group, $user)) {
      // User is blocked, access denied.
      throw new AccessDeniedHttpException();
    }

    if (Og::isMemberPending($group, $user)) {
      // User is pending, return them back.
      $message = $this->t('@user already has a pending membership for the  the group @group.', $params);
      $redirect = TRUE;
    }

    if (Og::isMember($group, $user)) {
      // User is already a member, return them back.
      $message = $this->t('You are already a member of the group @group.', $params);
      $redirect = TRUE;
    }

    if ($redirect) {
      drupal_set_message($message, 'warning');
      return new RedirectResponse($group->toUrl()->setAbsolute(TRUE)->toString());
    }

    if ($this->ogAccess->userAccess($group, 'subscribe', $user) || $this->ogAccess->userAccess($group, 'subscribe without approval', $user)) {
      // Show the user a subscription confirmation.
      return $this->formBuilder()->getForm('\Drupal\og\Form\GroupSubscribeConfirmForm', $group, $user);
    }

    throw new AccessDeniedHttpException();
  }

  /**
   * @param string $entity_type_id
   * @param string|int $entity_id
   */
  public function unsubscribe($entity_type_id, $entity_id) {
    $entity_storage = $this->entityTypeManager()->getStorage($entity_type_id);
    $group = $entity_storage->load($entity_id);

    $user = $this->currentUser();

    if (Og::isMemberBlocked($group, $user)) {
      // User is a blocked member.
      throw new AccessDeniedHttpException();
    }

    if ($group instanceof EntityOwnerInterface && $group->getOwnerId() == $user->id()) {
      // The user is the manager of the group.
      drupal_set_message(t('As the manager of %group, you can not leave the group.', array('%group' => $group->label())));

      return new RedirectResponse($group->toUrl()
        ->setAbsolute(TRUE)
        ->toString());
    }

    // Show the user a un-subscription confirmation.
    return $this
      ->formBuilder()
      ->getForm('\Drupal\og_ui\Form\GroupUnsubscribeConfirmForm', $group);


  }

}
