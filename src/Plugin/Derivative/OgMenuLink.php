<?php

namespace Drupal\og\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides menu links for OG admin routes.
 */
class OgMenuLink extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * Constructs a \Drupal\views\Plugin\Derivative\ViewsLocalTask instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $view_storage
   *   The view storage.
   */
  public function __construct(EntityStorageInterface $view_storage) {
    $this->viewStorage = $view_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')->getStorage('view')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = [];

    // @todo: Remove hardcoding
    $entity_type_id = 'node';
    $id = 'members';
    $base_route_name = "entity.$entity_type_id.og_admin_routes";

    $route_name = "$base_route_name.$id";

    $derivatives[$route_name] = [
      'title' => $this->t('Members'),
      'description' => $this->t('Manage members'),
      'parent' => $base_route_name,
      'route_name' =>  $route_name,
      'route_parameters' => [$entity_type_id => "{$entity_type_id}"],
    ];

    foreach ($derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $derivatives;
  }

}
