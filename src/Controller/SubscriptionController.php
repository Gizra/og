<?php

namespace Drupal\og\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgMembershipTypeInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\og\Og;

/**
 * Controller for OG subscription routes.
 */
class SubscriptionController extends ControllerBase {

  /**
   * OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a SubscriptionController object.
   *
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(OgAccessInterface $og_access, MessengerInterface $messenger) {
    $this->ogAccess = $og_access;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.access'),
      $container->get('messenger')
    );
  }

  /**
   * Subscribe a user to group.
   *
   * @param string $entity_type_id
   *   The entity type of the group entity.
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The entity ID of the group entity.
   * @param \Drupal\og\OgMembershipTypeInterface $membership_type
   *   The membership type to be used for creating the membership.
   *
   * @return mixed
   *   Redirect user or show access denied if they are not allowed to subscribe,
   *   otherwise provide a subscribe confirmation form.
   */
  public function subscribe($entity_type_id, EntityInterface $group, OgMembershipTypeInterface $membership_type) {
    if (!$group instanceof ContentEntityInterface) {
      // Not a valid entity.
      throw new AccessDeniedHttpException();
    }

    if (!Og::isGroup($entity_type_id, $group->bundle())) {
      // Not a valid group.
      throw new AccessDeniedHttpException();
    }

    $user = $this->entityTypeManager()->getStorage('user')->load($this->currentUser()->id());

    if ($user->isAnonymous()) {
      // Anonymous user can't request membership.
      $destination = $this->getDestinationArray();

      $user_login_url = Url::fromRoute('user.login', [], $destination)->toString();

      if ($this->config('user.settings')->get('register') === USER_REGISTER_ADMINISTRATORS_ONLY) {
        $params = [':login' => $user_login_url];
        $this->messenger->addMessage($this->t('In order to join any group, you must <a href=":login">login</a>. After you have successfully done so, you will need to request membership again.', $params));
      }
      else {
        $user_register_url = Url::fromRoute('user.register', [], $destination)->toString();
        $params = [':register' => $user_register_url, ':login' => $user_login_url];
        $this->messenger->addMessage($this->t('In order to join any group, you must <a href=":login">login</a> or <a href=":register">register</a> a new account. After you have successfully done so, you will need to request membership again.', $params));
      }

      return new RedirectResponse(Url::fromRoute('user.page')->setAbsolute(TRUE)->toString());
    }

    $redirect = FALSE;
    $message = '';
    $params = ['@user' => $user->getDisplayName()];

    // Show the group name only if user has access to it.
    $params['@group'] = $group->access('view', $user) ? $group->label() : $this->t('Private group');

    if (Og::isMemberBlocked($group, $user)) {
      // User is blocked, access denied.
      throw new AccessDeniedHttpException();
    }

    if (Og::isMemberPending($group, $user)) {
      // User is pending, return them back.
      $message = $this->t('You already have a pending membership for the the group @group.', $params);
      $redirect = TRUE;
    }

    if (Og::isMember($group, $user)) {
      // User is already a member, return them back.
      $message = $this->t('You are already a member of the group @group.', $params);
      $redirect = TRUE;
    }

    if ($redirect) {
      $this->messenger->addMessage($message, 'warning');
      return new RedirectResponse($group->toUrl()->setAbsolute(TRUE)->toString());
    }

    if (!$this->ogAccess->userAccess($group, 'subscribe', $user) && !$this->ogAccess->userAccess($group, 'subscribe without approval', $user)) {
      throw new AccessDeniedHttpException();
    }

    $membership = Og::createMembership($group, $user, $membership_type->id());
    $form = $this->entityFormBuilder()->getForm($membership, 'subscribe');
    return $form;

  }

  /**
   * Unsubscribe a user from group.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $group
   *   The group entity.
   *
   * @return mixed
   *   Redirect user or show access denied if they are not allowed to subscribe,
   *   otherwise provide an un-subscribe confirmation form.
   */
  public function unsubscribe(ContentEntityInterface $group) {
    $user = $this->currentUser();

    if (!$membership = Og::getMembership($group, $user, OgMembershipInterface::ALL_STATES)) {
      // User is not a member.
      throw new AccessDeniedHttpException();
    }

    if ($membership->getState() == OgMembershipInterface::STATE_BLOCKED) {
      // User is a blocked member.
      throw new AccessDeniedHttpException();
    }

    if ($group instanceof EntityOwnerInterface && $group->getOwnerId() == $user->id()) {
      // The user is the manager of the group.
      $this->messenger->addMessage($this->t('As the manager of %group, you can not leave the group.', ['%group' => $group->label()]));

      return new RedirectResponse($group->toUrl()
        ->setAbsolute()
        ->toString());
    }
    $form = $this->entityFormBuilder()->getForm($membership, 'unsubscribe');

    return $form;

  }

}
