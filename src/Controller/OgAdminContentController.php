<?php

declare(strict_types = 1);

namespace Drupal\og\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og\GroupTypeManagerInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * OgAdminContentController class.
 */
class OgAdminContentController extends ControllerBase {

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected $groupTypeManager;

  /**
   * The group audience helper.
   *
   * @var \Drupal\og\OgGroupAudienceHelperInterface
   */
  protected $groupAudienceHelper;

  /**
   * OgAdminContentController constructor.
   *
   * @param \Drupal\og\GroupTypeManagerInterface $group_type_manager
   *   The group type manager.
   * @param \Drupal\og\OgGroupAudienceHelperInterface $group_audience_helper
   *   The group audience helper.
   */
  public function __construct(GroupTypeManagerInterface $group_type_manager, OgGroupAudienceHelperInterface $group_audience_helper) {
    $this->groupTypeManager = $group_type_manager;
    $this->groupAudienceHelper = $group_audience_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.group_type_manager'),
      $container->get('og.group_audience_helper')
    );
  }

  /**
   * Display content form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   *
   * @return array
   *   The content form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function content(RouteMatchInterface $route_match) {
    $parameter_name = $route_match->getRouteObject()->getOption('_og_entity_type_id');
    /** @var \Drupal\Core\Entity\EntityInterface $group */
    $group = $route_match->getParameter($parameter_name);
    $bundle_ids = $this->groupTypeManager->getAllGroupContentBundleIds($group->getEntityTypeId());
    $results = [];
    foreach ($bundle_ids as $entity_type_id => $bundles) {
      $results[$entity_type_id] = [];
      foreach ($bundles as $bundle) {
        $fields = $this->groupAudienceHelper->getAllGroupAudienceFields($entity_type_id, $bundle);
        foreach ($fields as $field) {
          $storage = $this->entityTypeManager()
            ->getStorage($entity_type_id);
          $result = $storage->getQuery()
            ->condition($field->getName(), $group->id())
            ->execute();
          $results[$entity_type_id] = array_unique(array_merge($results[$entity_type_id], $result));
        }
      }
    }

    $build = [];
    foreach ($results as $entity_type_id => $entity_ids) {
      $build[$entity_type_id] = $this->renderListing($entity_type_id, $entity_ids);
    }

    return $build;
  }

  /**
   * Build render array.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param array $entity_ids
   *   The entity ids.
   *
   * @return null|array
   *   Render render array or null if no list builder.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see \Drupal\Core\Entity\EntityListBuilderInterface::render()
   */
  public function renderListing($entity_type_id, array $entity_ids) {
    if ($this->entityTypeManager()->hasHandler($entity_type_id, 'list_builder')) {
      /** @var \Drupal\Core\Entity\EntityListBuilder $list_builder */
      $list_builder = $this->entityTypeManager()
        ->getHandler($entity_type_id, 'list_builder');
      $entity_type = $this->entityTypeManager()->getStorage($entity_type_id)->getEntityType();
      $build['label'] = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $entity_type->getLabel(),
      ];
      $build['table'] = [
        '#type' => 'table',
        '#header' => $list_builder->buildHeader(),
        '#title' => $entity_type->getLabel(),
        '#rows' => [],
        '#empty' => $this->t('There are no @label yet.', ['@label' => $entity_type->getPluralLabel()]),
        '#cache' => [
          'contexts' => $entity_type->getListCacheContexts(),
          'tags' => $entity_type->getListCacheTags(),
        ],
      ];
      $entities = $this->entityTypeManager()->getStorage($entity_type_id)->loadMultiple($entity_ids);
      foreach ($entities as $entity) {
        if ($row = $list_builder->buildRow($entity)) {
          $build['table']['#rows'][$entity->id()] = $row;
        }
      }
      return $build;
    }
  }

}
