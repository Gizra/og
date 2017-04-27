<?php

namespace Drupal\og\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Blocks a group membership.
 *
 * @Action(
 *   id = "og_membership_block_action",
 *   label = @Translation("Block the selected membership(s)"),
 *   type = "og_membership"
 * )
 */
class BlockOgMembership extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs an BlockOgMembership object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The OG membership manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MembershipManagerInterface $membership_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->membershipManager = $membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('og.membership_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(OgMembership $membership = NULL) {
    if (!$membership) {
      return;
    }
    $membership->setState(OgMembershipInterface::STATE_BLOCKED)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\og\Entity\OgMembership $object */
    // Grant access if the user can administer all groups.
    $access = AccessResult::allowedIfHasPermission($account, 'administer group');

    // Grant access if the user can manage members in this group.
    $membership = $this->membershipManager->getMembership($object->getGroup(), $account);
    if ($membership) {
      $access->orIf(AccessResult::allowedIf($membership->hasPermission('manage members')));
    }

    // Deny access if the membership is already blocked.
    $access->andIf(AccessResult::forbiddenIf($object->getState() === OgMembershipInterface::STATE_BLOCKED));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
