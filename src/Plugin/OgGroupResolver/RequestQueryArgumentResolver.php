<?php

namespace Drupal\og\Plugin\OgGroupResolver;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\og\GroupTypeManagerInterface;
use Drupal\og\OgGroupResolverBase;
use Drupal\og\OgResolvedGroupCollectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolves the group from the query arguments on the request.
 *
 * This plugin inspects the current request and checks if there are query
 * arguments available that point to a group entity.
 *
 * @OgGroupResolver(
 *   id = "request_query_argument",
 *   label = "Group entity from query arguments",
 *   description = @Translation("Checks if the current request has query arguments that indicate the group context.")
 * )
 */
class RequestQueryArgumentResolver extends OgGroupResolverBase implements ContainerFactoryPluginInterface {

  /**
   * The query argument that holds the group entity type.
   */
  const GROUP_TYPE_ARGUMENT = 'og-type';

  /**
   * The query argument that holds the group entity ID.
   */
  const GROUP_ID_ARGUMENT = 'og-id';

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected $groupTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RequestQueryArgumentResolver.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\og\GroupTypeManagerInterface $group_type_manager
   *   The group type manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, GroupTypeManagerInterface $group_type_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->groupTypeManager = $group_type_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('og.group_type_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(OgResolvedGroupCollectionInterface $collection) {
    // Check if our arguments are present on the request.
    $query = $this->requestStack->getCurrentRequest()->query;
    if ($query->has(self::GROUP_TYPE_ARGUMENT) && $query->has(self::GROUP_ID_ARGUMENT)) {
      try {
        $storage = $this->entityTypeManager->getStorage($query->get(self::GROUP_TYPE_ARGUMENT));
      }
      catch (InvalidPluginDefinitionException $e) {
        // Invalid entity type specified, cannot resolve group.
        return;
      }

      // Load the entity and check if it is a group.
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      if ($entity = $storage->load($query->get(self::GROUP_ID_ARGUMENT))) {
        if ($this->groupTypeManager->isGroup($entity->getEntityTypeId(), $entity->bundle())) {
          // Only add a vote for the group if it already has been discovered by
          // a previous plugin. This will make sure that users cannot fake a
          // group context by messing with the query arguments.
          if ($collection->hasGroup($entity)) {
            $collection->addGroup($entity, ['url']);
          }
        }
      }
    }
  }

}
