<?php

namespace Drupal\og\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\OgAccessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for actions that change the state of a membership.
 */
abstract class ChangeOgMembershipStateBase extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs an ApprovePendingOgMembership object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OgAccessInterface $og_access) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('og.access')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(OgMembership $membership = NULL) {
    if (!$membership) {
      return;
    }
    $membership->setState($this->getTargetState())->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\og\Entity\OgMembership $object */
    // Deny access if the membership is not in the required state.
    $original_state = $this->getOriginalState();
    if ($original_state && $object->getState() !== $original_state) {
      $access = AccessResult::forbidden();
    }
    // Deny access if the membership is already in the target state.
    elseif ($object->getState() === $this->getTargetState()) {
      $access = AccessResult::forbidden();
    }
    // Deny access if the membership belongs to the group owner. The membership
    // state of the group owner should not be changed, it should always remain
    // active.
    elseif ($object->isOwner()) {
      $access = AccessResult::forbidden();
    }
    // Only grant access if the user can manage members in this group.
    else {
      $access = $this->ogAccess->userAccess($object->getGroup(), 'manage members', $account);
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * Returns the state the membership will have after the action is executed.
   *
   * @return string
   *   One of the following:
   *   - Drupal\og\OgMembershipInterface::STATE_ACTIVE
   *   - Drupal\og\OgMembershipInterface::STATE_PENDING
   *   - Drupal\og\OgMembershipInterface::STATE_BLOCKED
   */
  abstract public function getTargetState();

  /**
   * Returns the state the membership should have for the action to be executed.
   *
   * @return string|null
   *   Either NULL if the action does not require the membership to be in a
   *   particular state for the action to be executed, or one of the following:
   *   - Drupal\og\OgMembershipInterface::STATE_ACTIVE
   *   - Drupal\og\OgMembershipInterface::STATE_PENDING
   *   - Drupal\og\OgMembershipInterface::STATE_BLOCKED
   */
  abstract public function getOriginalState();

}
