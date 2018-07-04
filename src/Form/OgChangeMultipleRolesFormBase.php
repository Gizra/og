<?php

namespace Drupal\og\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgAccessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for forms that act on multiple roles.
 */
class OgChangeMultipleRolesFormBase extends FormBase {

  /**
   * The action plugin ID for which this is the confirmation form.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The memberships on which roles should be changed.
   *
   * @var \Drupal\og\OgMembershipInterface[]
   */
  protected $memberships = [];

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The temporary storage for the current user.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a OgChangeMultipleRolesFormbase object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, OgAccessInterface $og_access) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('og.access')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->pluginId . '_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $role_ids = array_keys($form_state->getValue('roles'));
    /** @var \Drupal\og\OgRoleInterface[] $roles */
    $roles = OgRole::loadMultiple($role_ids);
    foreach ($this->getMemberships() as $membership) {
      $changed = FALSE;
      foreach ($roles as $role) {
        $group = $membership->getGroup();
        if ($group->getEntityTypeId() === $role->getGroupType() && $group->bundle() === $role->getGroupBundle()) {
          if ($membership->hasRole($role->id())) {
            $changed = TRUE;
            $membership->revokeRole($role);
          }
        }
      }
      // Only save the membership if it has actually changed.
      if ($changed) {
        $membership->save();
      }
    }
  }

  /**
   * Controls access to the form.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(AccountInterface $account) {
    // Only grant access to the form if there are memberships to process, and if
    // the user has permission to manage members on all the memberships.
    $memberships = $this->getMemberships();
    $access = AccessResult::allowedIf(!empty($memberships));

    while ($access->isAllowed() && $membership = array_shift($memberships)) {
      $access = $this->ogAccess->userAccess($membership->getGroup(), 'manage members', $account);
    }

    return $access;
  }

  /**
   * Returns the temporary storage for the current user.
   *
   * @return \Drupal\Core\TempStore\PrivateTempStore
   *   The temporary storage for the current user.
   */
  protected function getTempStore() {
    if (empty($this->tempStore)) {
      $this->tempStore = $this->tempStoreFactory->get($this->pluginId);
    }
    return $this->tempStore;
  }

  /**
   * Returns an array of memberships on which to change roles.
   *
   * @return \Drupal\og\OgMembershipInterface[]
   *   The array of memberships.
   */
  protected function getMemberships() {
    if (empty($this->memberships)) {
      $membership_ids = $this->getTempStore()->get('membership_ids');
      if (!empty($membership_ids)) {
        $this->memberships = OgMembership::loadMultiple($membership_ids);
      }
    }
    return $this->memberships;
  }

  /**
   * Returns an array of group types for which memberships are present.
   *
   * @return array
   *   An array of group types, each value an array with two keys:
   *   - entity_type_id: The entity type ID of the group type.
   *   - bundle_id: The bundle ID of the group type.
   */
  protected function getGroupTypes() {
    $group_types = [];
    foreach ($this->getMemberships() as $membership) {
      $group = $membership->getGroup();
      $key = implode('-', [$group->getEntityTypeId(), $group->bundle()]);
      $group_types[$key] = [
        'entity_type_id' => $group->getEntityTypeId(),
        'bundle_id' => $group->bundle(),
      ];
    }

    return $group_types;
  }

}
