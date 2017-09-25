<?php

namespace Drupal\og\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgAccessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for operations to change a user's group membership.
 */
abstract class ChangeSingleOgMembershipRoleBase extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  use DependencyTrait;

  /**
   * The OG role entity type.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface
   */
  protected $entityType;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a ChangeSingleOgMembershipRoleBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The user role entity type.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeInterface $entity_type, OgAccessInterface $og_access) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityType = $entity_type;
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
      $container->get('entity_type.manager')->getDefinition('og_role'),
      $container->get('og.access')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $role_names = $this->getOgRoleLabels();
    reset($role_names);
    return [
      'role_name' => key($role_names),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = $this->getOgRoleLabels();
    reset($options);
    $form['role_name'] = [
      '#type' => 'radios',
      '#title' => t('Role'),
      '#options' => $options,
      '#default_value' => $this->configuration['role_name'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['role_name'] = $form_state->getValue('role_name');
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

  /**
   * Returns a list of OgRole labels.
   *
   * @return array
   *   An associative array of labels, keyed by OgRole ID.
   */
  protected function getOgRoleLabels() {
    /** @var \Drupal\og\OgRoleInterface[] $roles */
    $roles = OgRole::loadMultiple();
    // Do not return the default roles 'member' and 'non-member'. These are
    // required and cannot be added to or removed from a membership.
    $role_names = [];
    foreach ($roles as $role) {
      if (!$role->isRequired()) {
        $role_names[$role->getName()] = $role->label();
      }
    }

    return $role_names;
  }

}
