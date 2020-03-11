<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\og\OgGroupAudienceHelperInterface;

/**
 * OG access entity base class.
 */
abstract class OgAccessEntityTestBase extends OgAccessTestBase {

  /**
   * A test group content entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $groupContentEntity;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Mock a group content entity.
    $entity_type_id = $this->randomMachineName();
    $bundle = $this->randomMachineName();

    // Just a random entity ID.
    $entity_id = 20;

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->getListCacheTags()->willReturn([]);
    $entity_type->entityClassImplements(FieldableEntityInterface::class)->willReturn(TRUE);
    $entity_type->id()->willReturn($entity_type_id);

    $this->groupContentEntity = $this->prophesize(ContentEntityInterface::class);
    $this->groupContentEntity->id()->willReturn($entity_id);
    $this->groupContentEntity->bundle()->willReturn($bundle);
    $this->groupContentEntity->isNew()->willReturn(FALSE);
    $this->groupContentEntity->getEntityType()->willReturn($entity_type->reveal());
    $this->groupContentEntity->getEntityTypeId()->willReturn($entity_type_id);
    $this->addCache($this->groupContentEntity);

    // If the group audience helper is asked if the group content entity has any
    // group audience fields, it is expected that this will return TRUE.
    $this->groupAudienceHelper->hasGroupAudienceField($entity_type_id, $bundle)
      ->willReturn(TRUE);

    // It is expected that a list of entity operation permissions is retrieved
    // from the permission manager so that the passed in permission can be
    // checked against this list. Our permissions are not in the list, so it is
    // of no importance what we return here, an empty array is sufficient.
    $this->permissionManager->getDefaultEntityOperationPermissions($this->entityTypeId, $this->bundle, [$entity_type_id => [$bundle]])
      ->willReturn([]);

    // The group manager is expected to declare that this is not a group.
    $this->groupTypeManager->isGroup($entity_type_id, $bundle)->willReturn(FALSE);

    // Mock retrieval of field definitions.
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getType()->willReturn(OgGroupAudienceHelperInterface::GROUP_REFERENCE);
    $field_definition->getFieldStorageDefinition()
      ->willReturn($this->prophesize(FieldStorageDefinitionInterface::class)->reveal());
    $field_definition->getSetting('handler_settings')->willReturn([]);
    $field_definition->getName()->willReturn($this->randomMachineName());

    $entity_field_manager = $this->prophesize(EntityFieldManagerInterface::class);
    $entity_field_manager->getFieldDefinitions($entity_type_id, $bundle)->willReturn([$field_definition->reveal()]);

    $group_type_id = $this->group->getEntityTypeId();

    $storage = $this->prophesize(EntityStorageInterface::class);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getDefinition($entity_type_id)->willReturn($entity_type->reveal());
    $entity_type_manager->getStorage($group_type_id)->willReturn($storage->reveal());

    $container = \Drupal::getContainer();
    $container->set('entity_type.manager', $entity_type_manager->reveal());
    $container->set('entity_field.manager', $entity_field_manager->reveal());
  }

}
