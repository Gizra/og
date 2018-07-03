<?php

namespace Drupal\og\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\og\GroupTypeManagerInterface;
use Drupal\og\OgContextInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that shows recent group content for the current group.
 *
 * @Block(
 *   id = "og_recent_group_content",
 *   admin_label = @Translation("Recent group content")
 * )
 */
class RecentGroupContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The OG context provider.
   *
   * @var \Drupal\og\OgContextInterface
   */
  protected $ogContext;

  /**
   * The entity type manager, needed to load entities.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OG group type manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected $groupTypeManager;

  /**
   * The bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a RecentGroupContentBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\og\OgContextInterface $og_context
   *   The OG context provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\og\GroupTypeManagerInterface $group_type_manager
   *   The OG group type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The bundle info service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OgContextInterface $og_context, EntityTypeManagerInterface $entity_type_manager, GroupTypeManagerInterface $group_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->ogContext = $og_context;
    $this->entityTypeManager = $entity_type_manager;
    $this->groupTypeManager = $group_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('og.context'),
      $container->get('entity_type.manager'),
      $container->get('og.group_type_manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Default to the first entity type in the list.
    $bundles = $this->groupTypeManager->getAllGroupContentBundleIds();
    reset($bundles);
    $entity_type_default = key($bundles);

    // Enable all bundles by default.
    $bundle_defaults = [];
    foreach ($bundles as $entity_type_id => $bundle_ids) {
      foreach ($bundle_ids as $bundle_id) {
        $bundle_defaults[$entity_type_id][$bundle_id] = $bundle_id;
      }
    }

    return [
      'entity_type' => $entity_type_default,
      'bundles' => $bundle_defaults,
      'count' => 5,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['entity_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Entity type'),
      '#default_value' => $this->configuration['entity_type'],
      '#description' => $this->t('The entity type of the group content to show.'),
    ];

    $entity_type_options = [];
    foreach ($this->groupTypeManager->getAllGroupContentBundleIds() as $entity_type_id => $bundle_ids) {
      $entity_definition = $this->entityTypeManager->getDefinition($entity_type_id);
      $entity_type_options[$entity_type_id] = $entity_definition->getLabel();

      $bundle_options = [];
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      foreach ($bundle_ids as $bundle_id) {
        $bundle_options[$bundle_id] = $bundle_info[$bundle_id]['label'];
      }
      $form['bundles'][$entity_type_id] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Bundles'),
        '#default_value' => $this->configuration['bundles'][$entity_type_id],
        '#options' => $bundle_options,
        '#description' => $this->t('The group content bundles to show.'),
        '#states' => [
          'visible' => [
            ':input[name="settings[entity_type]"]' => ['value' => $entity_type_id],
          ],
        ],
      ];
    }

    $form['entity_type']['#options'] = $entity_type_options;

    $range = range(2, 20);
    $form['count'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of results to show'),
      '#default_value' => $this->configuration['count'],
      '#options' => array_combine($range, $range),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    foreach (['entity_type', 'bundles', 'count'] as $setting) {
      $this->configuration[$setting] = $form_state->getValue($setting);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Do not render anything if there is no group in the current context.
    if (empty($this->ogContext->getGroup())) {
      return [];
    }

    $list = array_map(function ($entity) {
      return [
        '#type' => 'link',
        '#title' => $entity->label(),
        '#url' => $entity->toUrl(),
      ];
    }, $this->getGroupContent());

    return [
      'list' => [
        '#theme' => 'item_list',
        '#items' => $list,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    if ($group = $this->ogContext->getGroup()) {
      $tags = Cache::mergeTags(Cache::buildTags('og-group-content', $group->getCacheTagsToInvalidate()), $tags);
    }
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // The block varies by user because of the access check on the query that
    // retrieves the group content.
    return Cache::mergeContexts(parent::getCacheContexts(), ['user', 'og_group_context']);
  }

  /**
   * Returns the most recent group content for the active group.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The most recent group content for the group which is currently active
   *   according to OgContext.
   */
  protected function getGroupContent() {
    $group = $this->ogContext->getGroup();
    $entity_type = $this->configuration['entity_type'];
    $bundles = array_filter($this->configuration['bundles'][$entity_type]);
    $definition = $this->entityTypeManager->getDefinition($entity_type);

    // Retrieve the fields which reference our entity type and bundle.
    $query = $this->entityTypeManager
      ->getStorage('field_storage_config')
      ->getQuery()
      ->condition('type', OgGroupAudienceHelperInterface::GROUP_REFERENCE)
      ->condition('entity_type', $entity_type);

    /** @var \Drupal\field\FieldStorageConfigInterface[] $fields */
    $fields = array_filter(FieldStorageConfig::loadMultiple($query->execute()), function (FieldStorageConfigInterface $field) use ($group) {
      $type_matches = $field->getSetting('target_type') === $group->getEntityTypeId();
      // If the list of target bundles is empty, it targets all bundles.
      $bundle_matches = empty($field->getSetting('target_bundles')) || in_array($group->bundle(), $field->getSetting('target_bundles'));
      return $type_matches && $bundle_matches;
    });

    // Compile the group content.
    $ids = [];
    foreach ($fields as $field) {
      // Query all group content that references the group through this field.
      $results = $this->entityTypeManager
        ->getStorage($entity_type)
        ->getQuery()
        ->condition($field->getName() . '.target_id', $group->id())
        ->condition($definition->getKey('bundle'), $bundles, 'IN')
        ->accessCheck()
        // @todo Add support for entity types that use a different column name
        //   for the created date.
        ->sort('created', 'DESC')
        ->range(0, $this->configuration['count'])
        ->execute();

      $ids = array_merge($ids, $results);
    }

    return $this->entityTypeManager->getStorage($entity_type)->loadMultiple($ids);
  }

}
