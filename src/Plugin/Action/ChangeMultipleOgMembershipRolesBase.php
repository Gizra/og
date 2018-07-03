<?php

namespace Drupal\og\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgMembershipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for actions that change multiple roles at once.
 */
abstract class ChangeMultipleOgMembershipRolesBase extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * The private temporary storage.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * Constructs a new RemoveMultipleOgMembershipRoles object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The private temporary storage factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OgAccessInterface $og_access, PrivateTempStoreFactory $temp_store_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ogAccess = $og_access;
    $this->tempStore = $temp_store_factory->get($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('og.access'),
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $memberships) {
    // Store the memberships to be processed in temporary storage. The actual
    // processing will be handled in the confirmation form.
    // @see \Drupal\og\Form\OgAddMultipleRolesForm
    // @see \Drupal\og\Form\OgRemoveMultipleRolesForm
    $membership_ids = array_map(function (OgMembershipInterface $membership) {
      return $membership->id();
    }, $memberships);
    $this->tempStore->set('membership_ids', $membership_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple([$object]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\og\Entity\OgMembership $object */
    // Only grant access if the user has permission to manage members in this
    // group.
    $access = $this->ogAccess->userAccess($object->getGroup(), 'manage members', $account);

    return $return_as_object ? $access : $access->isAllowed();
  }

}
