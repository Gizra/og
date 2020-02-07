<?php

namespace Drupal\og\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\OgAccessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deletes a group membership.
 *
 * @Action(
 *   id = "og_membership_delete_action",
 *   label = @Translation("Delete the selected membership(s)"),
 *   type = "og_membership"
 * )
 */
class DeleteOgMembership extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a DeleteOgMembership object.
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
    $membership->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\og\Entity\OgMembership $object */
    // Grant access if the user can manage members in this group.
    $access = $this->ogAccess->userAccess($object->getGroup(), 'manage members', $account);

    return $return_as_object ? $access : $access->isAllowed();
  }

}
