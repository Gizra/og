<?php

namespace Drupal\og\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgAccessInterface;
use Drupal\user\Entity\User;
use Drupal\og\Og;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide default OG selection handler.
 *
 * Note that the id is correctly defined as "og:default" and not the other way
 * around, as seen in most other default selection handler (e.g. "default:node")
 * as OG's selection handler is a wrapper around those entity specific default
 * ones. That is, the same selection handler will be returned no matter what is
 * the target type of the reference field. Internally, it will call the original
 * selection handler, and use it for building the queries.
 *
 * @EntityReferenceSelection(
 *   id = "og:default",
 *   label = @Translation("OG selection"),
 *   group = "og",
 *   weight = 1
 * )
 */
class OgSelection extends DefaultSelection {

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $ogMembershipManager;

  /**
   * Constructs a new SelectionBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   * @param \Drupal\og\MembershipManagerInterface $og_membership_manager
   *   The OG membership service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityRepositoryInterface $entity_repository, OgAccessInterface $og_access, MembershipManagerInterface $og_membership_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $module_handler, $current_user, $entity_field_manager, $entity_type_bundle_info, $entity_repository);
    $this->ogAccess = $og_access;
    $this->ogMembershipManager = $og_membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
      $container->get('og.access'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * Get the selection handler of the field.
   *
   * @return \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection
   *   Returns the selection handler.
   */
  public function getSelectionHandler() {
    $options = [
      'target_type' => $this->configuration['target_type'],
      // 'handler' key intentionally absent as we want the selection manager to
      // choose the best option.
      // @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager::getInstance()
      'handler_settings' => $this->configuration['handler_settings'],
    ];
    return \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
  }

  /**
   * Overrides ::buildEntityQuery.
   *
   * Return only group in the matching results.
   *
   * @param string|null $match
   *   (Optional) Text to match the label against. Defaults to NULL.
   * @param string $match_operator
   *   (Optional) The operation the matching should be done with. Defaults
   *   to "CONTAINS".
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The EntityQuery object with the basic conditions and sorting applied to
   *   it.
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    // Getting the original entity selection handler. OG selection handler using
    // the default selection handler of the entity, which the field reference
    // to, and add another logic to the query object i.e. check if the entities
    // bundle defined as group.
    $query = $this->getSelectionHandler()->buildEntityQuery($match, $match_operator);
    $target_type = $this->configuration['target_type'];
    $definition = \Drupal::entityTypeManager()->getDefinition($target_type);

    if ($bundle_key = $definition->getKey('bundle')) {
      $bundles = Og::groupTypeManager()->getGroupBundlesByEntityType($target_type);

      if (!$bundles) {
        // If there are no bundles defined, we can return early.
        return $query;
      }
      $query->condition($bundle_key, $bundles, 'IN');
    }

    // Get the identifier key of the entity.
    $identifier_key = $definition->getKey('id');

    if ($this->currentUser->isAnonymous()) {
      // @todo: Check if anonymous users should have access to any referenced
      // groups? What about groups that allow anonymous posts?
      return $query->condition($identifier_key, -1);
    }

    if ($this->currentUser->hasPermission('administer group')) {
      // User can see all the groups.
      return $query;
    }

    if (empty($this->configuration['entity'])) {
      // @todo: Find out why we have this scenario.
      return $query;
    }

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->configuration['entity'];
    $entity_type_id = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    $ids = [];
    foreach ($this->getUserGroups() as $group) {
      // Check user has "create" permission on this entity.
      $permission = "create $bundle content";
      if ($this->ogAccess->userAccess($group, $permission, $this->currentUser)->isAllowed()) {
        $ids[] = $group->id();
      }
    }

    if ($ids) {
      $query->condition($identifier_key, $ids, 'IN');
    }
    else {
      // User doesn't have permission to select any group so falsify this
      // query.
      $query->condition($identifier_key, -1);
    }

    return $query;
  }

  /**
   * Return all the user's groups.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Array with the user's group, or an empty array if none found.
   */
  protected function getUserGroups() {
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');
    $other_groups = $membership_manager->getUserGroups($this->currentUser->id());
    return isset($other_groups[$this->configuration['target_type']]) ? $other_groups[$this->configuration['target_type']] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Filter out the bundles that are not groups.
    $entity_type_id = $this->configuration['target_type'];
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $bundles_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);

    if ($entity_type->hasKey('bundle')) {

      $bundles = Og::groupTypeManager()->getGroupBundlesByEntityType($entity_type_id);
      foreach ($bundles as $bundle) {
        $bundle_options[$bundle] = $bundles_info[$bundle]['label'];
      }

      natsort($bundle_options);
      $form['target_bundles']['#options'] = $bundle_options;
    }

    return $form;
  }

}
