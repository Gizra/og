<?php

namespace Drupal\og\Plugin\views\access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\GroupTypeManager;
use Drupal\og\OgAccessInterface;
use Drupal\og\PermissionManagerInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides group permission-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "og_perm",
 *   title = @Translation("OG permission")
 * )
 */
class GroupPermission extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * The permission manager.
   *
   * @var \Drupal\og\PermissionManagerInterface
   */
  protected $permissionManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccess
   */
  protected $ogAccess;

  /**
   * The OG context provider.
   *
   * @var \Drupal\Core\Plugin\Context\ContextProviderInterface
   */
  protected $ogContext;

  /**
   * The group manager.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;

  /**
   * Constructs a GroupPermission object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\og\PermissionManagerInterface $permission_manager
   *   The permission handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $og_context
   *   The OG context provider.
   * @param \Drupal\og\GroupTypeManager $group_type_manager
   *   The group manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PermissionManagerInterface $permission_manager, EntityTypeManagerInterface $entity_type_manager, OgAccessInterface $og_access, ContextProviderInterface $og_context, GroupTypeManager $group_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->permissionManager = $permission_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->ogAccess = $og_access;
    $this->ogContext = $og_context;
    $this->groupTypeManager = $group_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('og.permission_manager'),
      $container->get('entity_type.manager'),
      $container->get('og.access'),
      $container->get('og.context'),
      $container->get('og.group_type_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $group = $this->getGroup();
    if ($group) {
      list(, $permission) = explode(':', $this->options['perm']);
      return $this->ogAccess->userAccess($group, $permission, $account);
    }
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    list($entity_type_id, $permission) = explode(':', $this->options['perm']);
    $route->setRequirement('_og_user_access_group', $permission);
    $route->setOption('_og_entity_type_id', $entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    $permissions = OptGroup::flattenOptions($this->getGroupPermissionOptions());
    if (isset($permissions[$this->options['perm']])) {
      return $permissions[$this->options['perm']];
    }
    return $this->options['perm'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['perm'] = [
      '#type' => 'select',
      '#options' => $this->getGroupPermissionOptions(),
      '#title' => $this->t('Permission'),
      '#default_value' => $this->options['perm'],
      '#required' => TRUE,
      '#description' => $this->t('Only users with the selected permission flag in a group retrieved from context will be able to access this display.'),

    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.permissions'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

  /**
   * Returns the group that is relevant in the current context.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The group, or NULL if no group is found.
   */
  protected function getGroup() {
    $contexts = $this->ogContext->getRuntimeContexts(['og']);
    if (!empty($contexts['og']) && $group = $contexts['og']->getContextValue()) {
      if ($group instanceof ContentEntityInterface) {
        return $group;
      }
    }
  }

  /**
   * Returns the full set of group permission options per entity type.
   *
   * @return array
   *   A multidimensional array of group permissions grouped by the entity type
   *   label.
   */
  protected function getGroupPermissionOptions() {
    $permissions = [];
    foreach ($this->groupTypeManager->getAllGroupBundles() as $group_entity_type_id => $group_bundles) {
      $entity_type_label = (string) $this->entityTypeManager->getDefinition($group_entity_type_id)->getLabel();
      foreach ($group_bundles as $group_bundle) {
        $group_content_bundle_ids = $this->groupTypeManager->getGroupContentBundleIdsByGroupBundle($group_entity_type_id, $group_bundle);
        foreach ($this->permissionManager->getDefaultPermissions($group_entity_type_id, $group_bundle, $group_content_bundle_ids) as $permission) {
          $permissions[$entity_type_label][$group_entity_type_id . ':' . $permission->getName()] = $permission->getTitle();
        }
      }
      asort($permissions[$entity_type_label]);
    }

    return $permissions;
  }

}
